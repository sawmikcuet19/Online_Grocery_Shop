<?php
@include 'config.php';
session_start();
$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:login.php');
    exit();
}

// Check if form is submitted and 'update_order' is set
if (isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];

    // Fetch the current payment status from the orders table
    $select_order = $conn->prepare("SELECT payment_status FROM `orders` WHERE id = ?");
    $select_order->execute([$order_id]);
    $fetch_order = $select_order->fetch(PDO::FETCH_ASSOC);
    $current_payment_status = $fetch_order['payment_status'];

    // Make sure 'update_payment' is set and sanitize it
    if (isset($_POST['update_payment'])) {
        $update_payment = $_POST['update_payment'];
        $update_payment = filter_var($update_payment, FILTER_SANITIZE_STRING);

        // Check if the payment status is different from the current one
        if ($update_payment != $current_payment_status) {

            // Update payment status in the orders table
            $update_orders = $conn->prepare("UPDATE `orders` SET payment_status = ? WHERE id = ?");
            $update_orders->execute([$update_payment, $order_id]);

            // If the payment status is changed to 'Cancelled'
            if ($update_payment == 'Cancelled') {
                // Fetch all related records from the restore table based on the order ID
                $select_restore = $conn->prepare("SELECT * FROM `restore` WHERE oid = ?");
                $select_restore->execute([$order_id]);

                // Loop through each related record in the restore table
                while ($fetch_restore = $select_restore->fetch(PDO::FETCH_ASSOC)) {
                    $pid = $fetch_restore['pid']; // Product ID from restore table
                    $quantity = $fetch_restore['quantity']; // Quantity of product in restore table

                    // Update the status in restore table to 'off'
                    $update_restore_status = $conn->prepare("UPDATE `restore` SET status = 'off' WHERE oid = ? AND pid = ?");
                    $update_restore_status->execute([$order_id, $pid]);

                    // Add the quantity back to the stock of the corresponding product
                    $update_product_stock = $conn->prepare("UPDATE `products` SET stock = stock + ? WHERE id = ?");
                    $update_product_stock->execute([$quantity, $pid]);
                }
                $message[] = 'Order status has been updated to "Cancelled" and product stock has been restored!';

            } elseif ($update_payment == 'pending' || $update_payment == 'completed') {
                // If payment status is changed from Cancelled to Pending or Completed
                // Fetch all related records from the restore table based on the order ID
                $select_restore = $conn->prepare("SELECT * FROM `restore` WHERE oid = ?");
                $select_restore->execute([$order_id]);

                while ($fetch_restore = $select_restore->fetch(PDO::FETCH_ASSOC)) {
                    $pid = $fetch_restore['pid']; // Product ID from restore table
                    $quantity = $fetch_restore['quantity']; // Quantity of product in restore table

                    if ($current_payment_status == 'Cancelled') {
                        // Decrease the stock (because the customer is receiving the items again)
                        $update_product_stock = $conn->prepare("UPDATE `products` SET stock = stock - ? WHERE id = ?");
                        $update_product_stock->execute([$quantity, $pid]);
                        $message[] = 'Stock has been reduced as the order has been reinstated from "Cancelled" to "Pending" or "Completed".';
                    }
                }

            } else {
                // For other cases like 'Completed' to 'Cancelled', etc.
                // Fetch all related records from the restore table based on the order ID
                $select_restore = $conn->prepare("SELECT * FROM `restore` WHERE oid = ?");
                $select_restore->execute([$order_id]);

                while ($fetch_restore = $select_restore->fetch(PDO::FETCH_ASSOC)) {
                    $pid = $fetch_restore['pid']; // Product ID from restore table
                    $quantity = $fetch_restore['quantity']; // Quantity of product in restore table

                    if ($update_payment == 'Cancelled') {
                        // Update the status in restore table to 'off'
                        $update_restore_status = $conn->prepare("UPDATE `restore` SET status = 'off' WHERE oid = ? AND pid = ?");
                        $update_restore_status->execute([$order_id, $pid]);

                        // Add the quantity back to the stock of the corresponding product
                        $update_product_stock = $conn->prepare("UPDATE `products` SET stock = stock + ? WHERE id = ?");
                        $update_product_stock->execute([$quantity, $pid]);

                        $message[] = 'Order status has been updated to "Cancelled" and product stock has been restored!';
                    } else {
                        // Update the status in restore table to 'on'
                        $update_restore_status = $conn->prepare("UPDATE `restore` SET status = 'on' WHERE oid = ? AND pid = ?");
                        $update_restore_status->execute([$order_id, $pid]);

                        // Subtract the quantity from the stock of the corresponding product
                        $update_product_stock = $conn->prepare("UPDATE `products` SET stock = stock - ? WHERE id = ?");
                        $update_product_stock->execute([$quantity, $pid]);

                        $message[] = 'Order status has been updated and product stock has been reduced!';
                    }
                }
            }
        } else {
            $message[] = 'Payment status is the same, no update needed.';
        }
    }
}

// Check if delete parameter is set
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_orders = $conn->prepare("DELETE FROM `orders` WHERE id = ?");
    $delete_orders->execute([$delete_id]);
    header('location:admin_orders.php');
    exit();
}

// Check if location form is submitted
if (isset($_POST['update_location'])) {
    $order_id = $_POST['order_id'];
    $location = $_POST['location'];

    // Check if a location already exists for the given oid
    $select_location = $conn->prepare("SELECT * FROM `location` WHERE oid = ?");
    $select_location->execute([$order_id]);

    if ($select_location->rowCount() > 0) {
        // Update location if it already exists
        $update_location = $conn->prepare("UPDATE `location` SET location = ? WHERE oid = ?");
        $update_location->execute([$location, $order_id]);
        $message[] = 'Location has been updated successfully!';
    } else {
        // Insert new location if it doesn't exist
        $insert_location = $conn->prepare("INSERT INTO `location` (oid, location) VALUES (?, ?)");
        $insert_location->execute([$order_id, $location]);
        $message[] = 'Location has been added successfully!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<style>
    /* CSS for the location update form with class .location-form */
    .location-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 10px;
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .location-form label {
        font-size: 14px;
        font-weight: bold;
        color: #333;
    }

    .location-form input[type="text"] {
        padding: 10px;
        font-size: 14px;
        border: 1px solid #ddd;
        border-radius: 5px;
        outline: none;
        transition: all 0.3s ease-in-out;
    }

    .location-form input[type="text"]:focus {
        border-color: #5c6bc0;
    }

    .location-form input[type="submit"] {
        padding: 10px 20px;
        font-size: 16px;
        color: white;
        background-color: #5c6bc0;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .location-form input[type="submit"]:hover {
        background-color: #3f51b5;
    }

    .location-form input[type="submit"]:active {
        background-color: #3949ab;
    }

    /* Optional: Add a little margin to the form for spacing */
    .location-form {
        margin-bottom: 20px;
    }

    /* You can add a responsive design tweak if necessary */
    @media (max-width: 768px) {
        .location-form {
            padding: 10px;
        }

        .location-form input[type="text"], .location-form input[type="submit"] {
            font-size: 14px;
        }
    }

</style>
<body>
    <?php include 'admin_header.php'; ?>

    <section class="placed-orders">
        <h1 class="title">Placed Orders</h1>
        <div class="box-container">
            <?php
                // Fetch all orders from the orders table
                $select_orders = $conn->prepare("SELECT * FROM `orders`");
                $select_orders->execute();

                if ($select_orders->rowCount() > 0) {
                    while ($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)) {
                        // Fetch location for the current order ID
                        $select_location = $conn->prepare("SELECT location FROM `location` WHERE oid = ?");
                        $select_location->execute([$fetch_orders['id']]);
                        $fetch_location = $select_location->fetch(PDO::FETCH_ASSOC);
                        $location = $fetch_location ? $fetch_location['location'] : 'None';
            ?>
            <div class="box">
                <p> User ID : <span><?= $fetch_orders['user_id']; ?></span> </p>
                <p> Placed On : <span><?= $fetch_orders['placed_on']; ?></span> </p>
                <p> Name : <span><?= $fetch_orders['name']; ?></span> </p>
                <p> Email : <span><?= $fetch_orders['email']; ?></span> </p>
                <p> Number : <span><?= $fetch_orders['number']; ?></span> </p>
                <p> Address : <span><?= $fetch_orders['address']; ?></span> </p>
                <p> Total Products : <span><?= $fetch_orders['total_products']; ?></span> </p>
                <p> Total Price : <span><?= $fetch_orders['total_price']; ?></span> </p>
                <p> Payment Method : <span><?= $fetch_orders['method']; ?></span> </p>
                <p> Order Status : 
                    <span style="background-color: <?= $fetch_orders['payment_status'] === 'Cancelled' ? '#8A0000' : 'transparent'; ?>; color: <?= $fetch_orders['payment_status'] === 'Cancelled' ? 'white' : 'initial'; ?>;">
                        <?= $fetch_orders['payment_status'] === 'Cancelled' ? 'Cancelled' : $fetch_orders['payment_status']; ?>
                    </span> 
                </p>

                <!-- Location Display and Update -->
                <form action="" method="POST" class="location-form">
                    <input type="hidden" name="order_id" value="<?= $fetch_orders['id']; ?>">
                    <label for="location">Location: </label>
                    <input type="text" name="location" value="<?= $location; ?>" required>
                    <input type="submit" name="update_location" value="Update Location">
                </form>


                <form action="" method="POST">
                    <input type="hidden" name="order_id" value="<?= $fetch_orders['id']; ?>">
                    <select name="update_payment" class="drop-down">
                        <option value="" selected disabled><?= $fetch_orders['payment_status']; ?></option>
                        <option value="pending">pending</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="completed">completed</option>
                    </select>
                    <div class="flex-btn">
                        <input type="submit" name="update_order" class="option-btn" value="update">
                    </div>
                </form>
            </div>
            <?php
                    }
                } else {
                    echo '<p class="empty">No orders placed yet!</p>';
                }
            ?>
        </div>
    </section>

    <script src="js/script.js"></script>
</body>
</html>
