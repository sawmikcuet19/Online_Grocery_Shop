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

if (isset($_POST['order'])) {
    // Collect and sanitize user input
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $number = filter_var($_POST['number'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);
    $method = filter_var($_POST['method'], FILTER_SANITIZE_STRING);
    $address = 'house no.' . $_POST['house'] . ', ' . $_POST['street'] . ', ' . $_POST['local'] . ', ' . $_POST['city'] . ', ' . $_POST['country'];
    $address = filter_var($address, FILTER_SANITIZE_STRING);
    $placed_on = date('d-M-Y');

    // Initialize cart total
    $cart_total = 0;
    $cart_products = [];

    // Query to get the user's cart items
    $cart_query = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
    $cart_query->execute([$user_id]);

    // Check each cart item and its stock
    if ($cart_query->rowCount() > 0) {
        while ($cart_item = $cart_query->fetch(PDO::FETCH_ASSOC)) {
            // Get the product's stock
            $product_id = $cart_item['pid'];
            $select_product = $conn->prepare("SELECT stock FROM `products` WHERE id = ?");
            $select_product->execute([$product_id]);
            $product = $select_product->fetch(PDO::FETCH_ASSOC);
            $stock = $product['stock'];

            // Check if the cart quantity exceeds the available stock
            if ($cart_item['quantity'] > $stock) {
                $cart_invalid = true;  // Set flag if quantity exceeds stock
                $cart_error_message = "Quantity exceeds available stock for " . $cart_item['name'] . ". Please adjust the quantity in your cart.";
                break;  // No need to check further items
            }

            // If the quantity is valid, calculate the total price
            $cart_products[] = $cart_item['name'] . ' ( ' . $cart_item['quantity'] . ' )';
            $sub_total = $cart_item['price'] * $cart_item['quantity'];
            $cart_total += $sub_total;
        }
    }

    // If the cart is invalid, display an error message and redirect to cart.php
    if ($cart_invalid) {
        $_SESSION['cart_error'] = $cart_error_message;
        header('location:cart.php');
        exit();
    }

    // If no issues with the cart, proceed with order placement
    $total_product = implode(', ', $cart_products);
    $order_query = $conn->prepare("SELECT * FROM `orders` WHERE name = ? AND number = ? AND email = ? AND method = ? AND address = ? AND total_products = ? AND total_price = ?");
    $order_query->execute([$name, $number, $email, $method, $address, $total_product, $cart_total]);

    if ($cart_total == 0) {
        $message[] = 'Your cart is empty!';
    } else if ($order_query->rowCount() > 0) {
        $message[] = 'Order is already placed!';
    } else {
        // Start a transaction to ensure the order and stock update are handled together
        try {
            $conn->beginTransaction();

            // Insert the order into the orders table
            $ps = 'pending';
            $insert_order = $conn->prepare("INSERT INTO `orders` (user_id, name, number, email, method, address, total_products, total_price, placed_on, payment_status) VALUES(?,?,?,?,?,?,?,?,?,?)");
            $insert_order->execute([$user_id, $name, $number, $email, $method, $address, $total_product, $cart_total, $placed_on, $ps]);

            // Get the order ID of the newly inserted order
            $order_id = $conn->lastInsertId();

            // Insert into the restore table
            $cart_query->execute([$user_id]);
            while ($cart_item = $cart_query->fetch(PDO::FETCH_ASSOC)) {
                $product_id = $cart_item['pid'];
                $quantity_ordered = $cart_item['quantity'];

                // Insert into restore table
                $insert_restore = $conn->prepare("INSERT INTO `restore` (oid, user_id, pid, quantity) VALUES(?, ?, ?, ?)");
                $insert_restore->execute([$order_id, $user_id, $product_id, $quantity_ordered]);
            }

            // Update stock for each product in the cart
            $cart_query->execute([$user_id]);
            while ($cart_item = $cart_query->fetch(PDO::FETCH_ASSOC)) {
                $product_id = $cart_item['pid'];
                $quantity_ordered = $cart_item['quantity'];

                // Update the stock in the products table
                $update_stock = $conn->prepare("UPDATE `products` SET stock = stock - ? WHERE id = ?");
                $update_stock->execute([$quantity_ordered, $product_id]);
            }

            // Insert into the payments table if the payment method is Credit/Debit card
            if ($method == 'credit card' || $method == 'Debit card') {
                $card = filter_var($_POST['card'], FILTER_SANITIZE_STRING);
                $pin = md5($_POST['pass']);
                $pin = filter_var($pass, FILTER_SANITIZE_STRING);

                $insert_payment = $conn->prepare("INSERT INTO `payments` (oid, card_type, card, pin, date) VALUES (?, ?, ?, ?, ?)");
                $insert_payment->execute([$order_id, $method, $card, $pin, $placed_on]);
            }

            // Remove items from the cart after the order is placed
            $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
            $delete_cart->execute([$user_id]);

            // Commit the transaction
            $conn->commit();
            $message[] = 'Order placed successfully!';
            // Redirect based on payment method
        } catch (Exception $e) {
            // If something goes wrong, rollback the transaction
            $conn->rollBack();
            $message[] = 'Failed to place the order. Please try again later.';
        }
    }
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
            $cart_grand_total = 0;
            $select_cart_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $select_cart_items->execute([$user_id]);

            if ($select_cart_items->rowCount() > 0) {
                while ($fetch_cart_items = $select_cart_items->fetch(PDO::FETCH_ASSOC)) {
                    $cart_total_price = $fetch_cart_items['price'] * $fetch_cart_items['quantity'];
                    $cart_grand_total += $cart_total_price;
                    ?>
                    <p> <?= $fetch_cart_items['name']; ?> <span>(<?= $fetch_cart_items['price'] . ' /- x ' . $fetch_cart_items['quantity'] ?>)</span> </p>
                    <?php
                }
            } else {
                echo '<p class="empty">Your cart is empty!</p>';
            }
        ?>
        <div class="grand-total">Grand total: <span><?= $cart_grand_total; ?>/-</span></div>
    </section>

    <section class="checkout-orders">
        <form action="" method="POST">
            <h3>Place your order</h3>
            <div class="flex">
                <div class="inputBox">
                    <span>Your name:</span>
                    <input type="text" name="name" placeholder="Enter your name" class="box" required>
                </div>
                <div class="inputBox">
                    <span>Your number:</span>
                    <input type="number" name="number" placeholder="Enter your phone number" class="box" required>
                </div>
                <div class="inputBox">
                    <span>Your email:</span>
                    <input type="email" name="email" placeholder="Enter your email" class="box" required>
                </div>
                <div class="inputBox">
                    <span>Payment method:</span>
                    <select name="method" class="box" required onchange="togglePaymentFields(this)">
                        <option value="cash on delivery">Cash on delivery</option>
                        <option value="credit card">Credit card</option>
                        <option value="Debit card">Debit card</option>
                    </select>
                </div>
                <div class="inputBox">
                    <span>Address line 01:</span>
                    <input type="text" name="house" placeholder="Enter house number" class="box" required>
                </div>
                <div class="inputBox">
                    <span>Address line 02:</span>
                    <input type="text" name="street" placeholder="Enter street name/number" class="box" required>
                </div>
                <div class="inputBox">
                    <span>Local area name:</span>
                    <input type="text" name="local" placeholder="Enter local area name" class="box" required>
                </div>
                <div class="inputBox">
                    <span>City name:</span>
                    <input type="text" name="city" placeholder="Enter city name" class="box" required>
                </div>
                <div class="inputBox">
                    <span>Country:</span>
                    <input type="text" name="country" placeholder="Enter country name" class="box" required>
                </div>
                
                <!-- Card details, shown only for credit/debit card payments -->
                <div class="inputBox" id="card-details" style="display: none;">
                    <span>Card number:</span>
                    <input type="text" name="card" placeholder="Enter card number" class="box">
                </div>
                <div class="inputBox" id="card-pin" style="display: none;">
                    <span>PIN:</span>
                    <input type="password" name="pin" placeholder="Enter card PIN" class="box">
                </div>

                <input type="submit" name="order" class="btn <?= ($cart_grand_total > 1) ? '' : 'disabled' ?>" value="Place order">
            </div>
        </form>
    </section>

    <?php include 'footer.php'; ?>

    <script>
        function togglePaymentFields(select) {
            var cardDetails = document.getElementById("card-details");
            var cardPin = document.getElementById("card-pin");
            if (select.value === 'credit card' || select.value === 'Debit card') {
                cardDetails.style.display = "block";
                cardPin.style.display = "block";
            } else {
                cardDetails.style.display = "none";
                cardPin.style.display = "none";
            }
        }
    </script>

    <script src="js/script.js"></script>
</body>
</html>
