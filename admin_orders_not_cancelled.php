<?php
@include 'config.php';
session_start();
$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:login.php');
}

// Update payment status if the form is submitted
if (isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];

    // Sanitize and update payment status
    if (isset($_POST['update_payment'])) {
        $update_payment = $_POST['update_payment'];
        $update_payment = filter_var($update_payment, FILTER_SANITIZE_STRING);

        // Update the payment status in the orders table
        $update_orders = $conn->prepare("UPDATE `orders` SET payment_status = ? WHERE id = ?");
        $update_orders->execute([$update_payment, $order_id]);
        $message[] = 'Payment status has been updated!';

        // Get the order details to fetch the related product id and quantity
        $select_order = $conn->prepare("SELECT * FROM `orders` WHERE id = ?");
        $select_order->execute([$order_id]);
        $order_details = $select_order->fetch(PDO::FETCH_ASSOC);

        $product_id = $order_details['pid'];  // Get the product id
        $quantity = $order_details['total_products'];  // Assuming `total_products` contains the quantity

        // Check if the payment is cancelled
        if ($update_payment === 'Cancelled') {
            // Update the status in the restore table to 'off'
            $update_restore = $conn->prepare("UPDATE `restore` SET status = 'off' WHERE oid = ?");
            $update_restore->execute([$order_id]);

            // Add the quantity of the product back to the products table (increase stock)
            $update_product_stock = $conn->prepare("UPDATE `products` SET stock = stock + ? WHERE id = ?");
            $update_product_stock->execute([$quantity, $product_id]);
        } else {
            // Update the status in the restore table to 'on'
            $update_restore = $conn->prepare("UPDATE `restore` SET status = 'on' WHERE oid = ?");
            $update_restore->execute([$order_id]);

            // Subtract the quantity from the products table (decrease stock)
            $update_product_stock = $conn->prepare("UPDATE `products` SET stock = stock - ? WHERE id = ?");
            $update_product_stock->execute([$quantity, $product_id]);
        }
    }
}

// Delete order
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_orders = $conn->prepare("DELETE FROM `orders` WHERE id = ?");
    $delete_orders->execute([$delete_id]);
    header('location:admin_orders.php');
}

// Fetch orders excluding those with the "Cancelled" status
$select_orders = $conn->prepare("SELECT * FROM `orders` WHERE payment_status != 'Cancelled'");
$select_orders->execute();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <section class="placed-orders">
        <h1 class="title">Placed Orders (Excluding Cancelled)</h1>

        <div class="box-container">
            <?php
            if ($select_orders->rowCount() > 0) {
                while ($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)) {
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


                    </div>
            <?php
                }
            } else {
                echo '<p class="empty">No orders found!</p>';
            }
            ?>
        </div>
    </section>

    <script src="js/script.js"></script>
</body>

</html>
