<?php
@include 'config.php';
session_start();
$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:login.php');
    exit();
}

// Initialize message array to store status updates for display
$message = [];

// Update payment status if the form is submitted
if (isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];

    // Sanitize and update payment status
    if (isset($_POST['update_payment'])) {
        $update_payment = $_POST['update_payment'];
        $update_payment = filter_var($update_payment, FILTER_SANITIZE_STRING);

        // Get current payment status of the order
        $select_order = $conn->prepare("SELECT payment_status FROM `orders` WHERE id = ?");
        $select_order->execute([$order_id]);
        $current_status = $select_order->fetch(PDO::FETCH_ASSOC)['payment_status'];

        // Update the payment status in the orders table
        $update_orders = $conn->prepare("UPDATE `orders` SET payment_status = ? WHERE id = ?");
        $update_orders->execute([$update_payment, $order_id]);
        $message[] = 'Payment status has been updated!';

        // Get all restore entries for this order
        $select_restore = $conn->prepare("SELECT * FROM `restore` WHERE oid = ?");
        $select_restore->execute([$order_id]);

        // Check if there are any restore entries
        if ($select_restore->rowCount() > 0) {
            while ($restore_data = $select_restore->fetch(PDO::FETCH_ASSOC)) {
                $product_id = $restore_data['pid'];
                $quantity = $restore_data['quantity'];

                // Handle stock change based on the payment status transition
                if ($current_status === 'Cancelled' && $update_payment !== 'Cancelled') {
                    // From Cancelled to Pending or Completed: Decrease stock
                    $update_product = $conn->prepare("UPDATE `products` SET stock = stock - ? WHERE id = ?");
                    $update_product->execute([$quantity, $product_id]);

                    // Set restore status to 'on' (item is now "restored" to active order)
                    $update_restore = $conn->prepare("UPDATE `restore` SET status = 'on' WHERE oid = ?");
                    $update_restore->execute([$order_id]);
                } elseif ($current_status !== 'Cancelled' && $update_payment === 'Cancelled') {
                    // From Pending/Completed to Cancelled: Increase stock (restoring the product)
                    $update_product = $conn->prepare("UPDATE `products` SET stock = stock + ? WHERE id = ?");
                    $update_product->execute([$quantity, $product_id]);

                    // Set restore status to 'off' (product is returned and not needed anymore)
                    $update_restore = $conn->prepare("UPDATE `restore` SET status = 'off' WHERE oid = ?");
                    $update_restore->execute([$order_id]);
                }
                // If there's no change in payment status (Pending to Completed or vice versa), do nothing for stock.
            }
        }
    }
}

// Delete order
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_orders = $conn->prepare("DELETE FROM `orders` WHERE id = ?");
    $delete_orders->execute([$delete_id]);
    header('location:admin_orders.php');
    exit();
}

// Check for status filter (Cancelled or others)
if (isset($_GET['status']) && $_GET['status'] === 'Cancelled') {
    $status_filter = 'Cancelled';
    $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE payment_status = ?");
    $select_orders->execute([$status_filter]);
} else {
    // Fetch all orders if no status filter is applied
    $select_orders = $conn->prepare("SELECT * FROM `orders`");
    $select_orders->execute();
}
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
        <h1 class="title">Placed Orders (Only Cancelled)</h1>
        
        <!-- Display any update messages -->
        <?php
            if (!empty($message) && is_array($message)) {
                foreach ($message as $msg) {
                    echo "<p class='success'>$msg</p>";
                }
            }
        ?>
        
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
                <p> Order Status : <span style="background-color: <?= $fetch_orders['payment_status'] === 'Cancelled' ? '#8A0000' : 'transparent'; ?>; color: <?= $fetch_orders['payment_status'] === 'Cancelled' ? 'white' : 'initial'; ?>;"><?= $fetch_orders['payment_status'] === 'Cancelled' ? 'Cancelled' : $fetch_orders['payment_status']; ?></span> </p>

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
