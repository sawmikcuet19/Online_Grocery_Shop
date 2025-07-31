<?php
@include 'config.php';
session_start();
$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
    header('location:login.php');
    exit();
}

// Flag to track if there's an issue with the cart
$cart_invalid = false;
$cart_error_message = ''; // Variable to store the error message

// Variable for coupon handling
$coupon_name = '';
$discounted_price = 0;
$discount_error = '';
$final_price = 0;

if (isset($_POST['order']) || isset($_POST['see_discount'])) {
    // Collect and sanitize user input
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $number = filter_var($_POST['number'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);
    $method = filter_var($_POST['method'], FILTER_SANITIZE_STRING);
    $address = 'house no.' . $_POST['house'] . ', ' . $_POST['street'] . ', ' . $_POST['local'] . ', ' . $_POST['city'] . ', ' . $_POST['country'];
    $address = filter_var($address, FILTER_SANITIZE_STRING);
    $placed_on = date('d-M-Y');

    // If coupon code is entered, sanitize and validate it
    if (!empty($_POST['coupon_name'])) {
        $coupon_name = filter_var($_POST['coupon_name'], FILTER_SANITIZE_STRING);

        // Check if the coupon exists and retrieve the discount percentage
        $coupon_query = $conn->prepare("SELECT * FROM coupon_table WHERE coupon_name = ?");
        $coupon_query->execute([$coupon_name]);

        if ($coupon_query->rowCount() > 0) {
            $coupon = $coupon_query->fetch(PDO::FETCH_ASSOC);
            $percentage = $coupon['percentage'];

            // Check if the user has chances for this coupon
            $coupon_connection_query = $conn->prepare("SELECT * FROM coupon_connection WHERE coupon_id = ? AND user_id = ?");
            $coupon_connection_query->execute([$coupon['id'], $user_id]);

            if ($coupon_connection_query->rowCount() > 0) {
                // Coupon exists for this user, check chances
                $coupon_connection = $coupon_connection_query->fetch(PDO::FETCH_ASSOC);
                if ($coupon_connection['chances'] > 0) {
                    // Apply discount
                    $cart_total = calculateCartTotal($user_id);
                    $discounted_price = $cart_total - ($cart_total * $percentage / 100);
                    $final_price = $discounted_price;
                } else {
                    $discount_error = 'No chances left for this coupon.';
                }
            } else {
                // User doesn't have a record in coupon_connection, show discounted price
                $cart_total = calculateCartTotal($user_id);
                $discounted_price = $cart_total - ($cart_total * $percentage / 100);
                $final_price = $discounted_price;
            }
        } else {
            $discount_error = 'Invalid coupon code!';
        }
    }

    // If the order button was pressed, proceed with the order
    if (isset($_POST['order']) && empty($discount_error)) {
        $cart_products = [];
        $cart_total = 0;
        $cart_query = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
        $cart_query->execute([$user_id]);

        if ($cart_query->rowCount() > 0) {
            while ($cart_item = $cart_query->fetch(PDO::FETCH_ASSOC)) {
                // Calculate total price for each cart item
                $cart_products[] = $cart_item['name'] . ' (' . $cart_item['quantity'] . ')';
                $sub_total = $cart_item['price'] * $cart_item['quantity'];
                $cart_total += $sub_total;
            }

            // Use discounted price if coupon applied
            if ($discounted_price > 0) {
                $cart_total = $discounted_price;
            }

            $total_product = implode(', ', $cart_products);

            // Insert the order into the database
            $order_query = $conn->prepare("SELECT * FROM orders WHERE name = ? AND number = ? AND email = ? AND method = ? AND address = ? AND total_products = ? AND total_price = ?");
            $order_query->execute([$name, $number, $email, $method, $address, $total_product, $cart_total]);

            if ($order_query->rowCount() > 0) {
                $message[] = 'Order is already placed!';
            } else {
                // Start a transaction to ensure the order and stock update are handled together
                try {
                    $conn->beginTransaction();

                    // Insert the order into the orders table
                    $ps = 'pending';
                    $insert_order = $conn->prepare("INSERT INTO orders (user_id, name, number, email, method, address, total_products, total_price, placed_on, payment_status) VALUES(?,?,?,?,?,?,?,?,?,?)");
                    $insert_order->execute([$user_id, $name, $number, $email, $method, $address, $total_product, $cart_total, $placed_on, $ps]);

                    // Get the order ID of the newly inserted order
                    $order_id = $conn->lastInsertId();

                    // Insert into the restore table
                    $cart_query->execute([$user_id]);
                    while ($cart_item = $cart_query->fetch(PDO::FETCH_ASSOC)) {
                        $product_id = $cart_item['pid'];
                        $quantity_ordered = $cart_item['quantity'];

                        // Insert into restore table
                        $insert_restore = $conn->prepare("INSERT INTO restore (oid, user_id, pid, quantity) VALUES(?, ?, ?, ?)");
                        $insert_restore->execute([$order_id, $user_id, $product_id, $quantity_ordered]);
                    }

                    // Update stock for each product in the cart
                    $cart_query->execute([$user_id]);
                    while ($cart_item = $cart_query->fetch(PDO::FETCH_ASSOC)) {
                        $product_id = $cart_item['pid'];
                        $quantity_ordered = $cart_item['quantity'];

                        // Update the stock in the products table
                        $update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                        $update_stock->execute([$quantity_ordered, $product_id]);
                    }

                    // Insert into the payments table if the payment method is Credit/Debit card
                    if ($method == 'credit card' || $method == 'Debit card') {
                        $card = filter_var($_POST['card'], FILTER_SANITIZE_STRING);
                        $pin = md5($_POST['pass']);
                        $pin = filter_var($pass, FILTER_SANITIZE_STRING);

                        $insert_payment = $conn->prepare("INSERT INTO payments (oid, card_type, card, pin, date) VALUES (?, ?, ?, ?, ?)");
                        $insert_payment->execute([$order_id, $method, $card, $pin, $placed_on]);
                    }

                    // Remove items from the cart after the order is placed
                    $delete_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                    $delete_cart->execute([$user_id]);

                    // Commit the transaction
                    $conn->commit();
                    $message[] = 'Order placed successfully!';
                } catch (Exception $e) {
                    // If something goes wrong, rollback the transaction
                    $conn->rollBack();
                    $message[] = 'Failed to place the order. Please try again later.';
                }
            }
        }
    }
}

function calculateCartTotal($user_id) {
    global $conn;
    $cart_grand_total = 0;
    $select_cart_items = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
    $select_cart_items->execute([$user_id]);

    while ($fetch_cart_items = $select_cart_items->fetch(PDO::FETCH_ASSOC)) {
        $cart_total_price = $fetch_cart_items['price'] * $fetch_cart_items['quantity'];
        $cart_grand_total += $cart_total_price;
    }

    return $cart_grand_total;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css"> 
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="display-orders">
        <?php
            $cart_grand_total = calculateCartTotal($user_id);

            // Show the discounted price if available
            echo '<p>Total Price: ' . $cart_grand_total . '/-</p>';

            if ($discounted_price > 0) {
                echo '<p>Discounted Price: ' . $discounted_price . '/-</p>';
            }

            // Display the discount error message if there is one
            if ($discount_error) {
                echo '<p class="error">' . $discount_error . '</p>';
            }
        ?>
    </section>

    <section class="checkout-orders">
        <form action="" method="POST" id="orderForm">
            <h3>Place your order</h3>
            <div class="flex">
                <div class="inputBox">
                    <span>Your name:</span>
                    <input type="text" name="name" placeholder="Enter your name" class="box" value="<?= isset($name) ? $name : '' ?>" required>
                </div>
                <div class="inputBox">
                    <span>Your number:</span>
                    <input type="number" name="number" placeholder="Enter your phone number" class="box" value="<?= isset($number) ? $number : '' ?>" required>
                </div>
                <div class="inputBox">
                    <span>Your email:</span>
                    <input type="email" name="email" placeholder="Enter your email" class="box" value="<?= isset($email) ? $email : '' ?>" required>
                </div>
                <div class="inputBox">
                    <span>Payment method:</span>
                    <select name="method" class="box" required onchange="togglePaymentFields(this)">
                        <option value="cash on delivery" <?= isset($method) && $method == 'cash on delivery' ? 'selected' : '' ?>>Cash on delivery</option>
                        <option value="credit card" <?= isset($method) && $method == 'credit card' ? 'selected' : '' ?>>Credit card</option>
                        <option value="Debit card" <?= isset($method) && $method == 'Debit card' ? 'selected' : '' ?>>Debit card</option>
                    </select>
                </div>

                <!-- Coupon code input -->
                <div class="inputBox">
                    <span>Coupon Code (Optional):</span>
                    <input type="text" name="coupon_name" placeholder="Enter coupon code" class="box" value="<?= isset($coupon_name) ? $coupon_name : '' ?>">
                </div>

                <div class="inputBox">
                    <span>Address line 01:</span>
                    <input type="text" name="house" placeholder="Enter house number" class="box" value="<?= isset($_POST['house']) ? $_POST['house'] : '' ?>" required>
                </div>
                <div class="inputBox">
                    <span>Address line 02:</span>
                    <input type="text" name="street" placeholder="Enter street name/number" class="box" value="<?= isset($_POST['street']) ? $_POST['street'] : '' ?>" required>
                </div>
                <div class="inputBox">
                    <span>Local area name:</span>
                    <input type="text" name="local" placeholder="Enter local area name" class="box" value="<?= isset($_POST['local']) ? $_POST['local'] : '' ?>" required>
                </div>
                <div class="inputBox">
                    <span>City name:</span>
                    <input type="text" name="city" placeholder="Enter city name" class="box" value="<?= isset($_POST['city']) ? $_POST['city'] : '' ?>" required>
                </div>
                <div class="inputBox">
                    <span>Country:</span>
                    <input type="text" name="country" placeholder="Enter country name" class="box" value="<?= isset($_POST['country']) ? $_POST['country'] : '' ?>" required>
                </div>

                <!-- Card details, shown only for credit/debit card payments -->
                <div class="inputBox" id="card-details" style="display: none;">
                    <span>Card number:</span>
                    <input type="text" name="card" placeholder="Enter card number" class="box">
                </div>
                <div class="inputBox" id="card-details" style="display: none;">
                    <span>Card Pin:</span>
                    <input type="password" name="pass" placeholder="Enter card pin" class="box">
                </div>

                <input type="submit" name="see_discount" value="See Discount Price" class="btn">
                <input type="submit" name="order" value="Place Order" class="btn">
            </div>
        </form>
    </section>

    <script>
        function togglePaymentFields(paymentMethod) {
            const cardDetails = document.getElementById('card-details');
            if (paymentMethod.value === 'credit card' || paymentMethod.value === 'Debit card') {
                cardDetails.style.display = 'block';
            } else {
                cardDetails.style.display = 'none';
            }
        }
    </script>
</body>
</html>
