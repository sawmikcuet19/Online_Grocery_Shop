<?php
@include 'config.php';
session_start();
$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:login.php');
}

if (isset($_POST['add_coupon'])) {
    $coupon_name = $_POST['coupon_name'];
    $coupon_name = filter_var($coupon_name, FILTER_SANITIZE_STRING);

    $percentage = $_POST['percentage'];
    $percentage = filter_var($percentage, FILTER_SANITIZE_NUMBER_INT);

    // Check if the coupon name already exists
    $select_coupons = $conn->prepare("SELECT * FROM `coupon_table` WHERE coupon_name = ?");
    $select_coupons->execute([$coupon_name]);

    if ($select_coupons->rowCount() > 0) {
        $message[] = 'Coupon name already exists!';
    } else {
        // Insert the coupon into the database
        $insert_coupon = $conn->prepare("INSERT INTO `coupon_table`(coupon_name, percentage) VALUES(?, ?)");
        $insert_coupon->execute([$coupon_name, $percentage]);

        if ($insert_coupon) {
            $message[] = 'New coupon added successfully!';
        }
    }
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    // Delete the coupon from the database
    $delete_coupon = $conn->prepare("DELETE FROM `coupon_table` WHERE id = ?");
    $delete_coupon->execute([$delete_id]);

    header('location:admin_coupons.php');
}

if (isset($_GET['update'])) {
    $update_id = $_GET['update'];
    $new_percentage = $_GET['percentage'];

    // Update the coupon's percentage in the database
    $update_coupon = $conn->prepare("UPDATE `coupon_table` SET percentage = ? WHERE id = ?");
    $update_coupon->execute([$new_percentage, $update_id]);

    header('location:admin_coupons.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupons</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<style>
    /* General Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Arial', sans-serif;
    }

    body {
        background-color: #f4f7fc;
        color: #333;
        line-height: 1.6;
    }

    a {
        text-decoration: none;
        color: inherit;
    }

    .container {
        width: 90%;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Header Section */
    .admin-header {
        background-color: #4CAF50;
        color: white;
        padding: 20px 0;
        text-align: center;
    }

    .admin-header h1 {
        font-size: 24px;
        font-weight: 600;
    }

    /* Section Titles */
    .title {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 20px;
        color: #333;
    }

    /* Add Coupon Section */
    .add-coupon {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 40px;
    }

    .add-coupon .inputBox {
        margin-bottom: 20px;
    }

    .add-coupon input[type="text"],
    .add-coupon input[type="number"] {
        width: 100%;
        padding: 12px;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-top: 10px;
        background-color: #f9f9f9;
    }

    .add-coupon input[type="submit"] {
        width: 100%;
        padding: 15px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 18px;
        transition: background-color 0.3s ease;
    }

    .add-coupon input[type="submit"]:hover {
        background-color: #45a049;
    }

    /* Show Coupons Table */
    .show-coupons {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .coupon-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .coupon-table th,
    .coupon-table td {
        padding: 12px;
        text-align: center;
        border: 1px solid #ddd;
        font-size: 16px;
    }

    .coupon-table th {
        background-color: #f4f4f4;
        color: #333;
    }

    .coupon-table tr:nth-child(even) {
        background-color: #fafafa;
    }

    .coupon-table tr:hover {
        background-color: #f1f1f1;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .coupon-table td a {
        padding: 8px 16px;
        background-color: #007bff;
        color: white;
        border-radius: 6px;
        transition: background-color 0.3s ease;
    }

    .coupon-table td a.option-btn {
        background-color: #28a745;
    }

    .coupon-table td a.delete-btn {
        background-color: #dc3545;
    }

    .coupon-table td a:hover {
        background-color: #0056b3;
    }

    .coupon-table td a.option-btn:hover {
        background-color: #218838;
    }

    .coupon-table td a.delete-btn:hover {
        background-color: #c82333;
    }

    /* Empty Table Message */
    .empty {
        text-align: center;
        font-size: 18px;
        color: #888;
        padding: 20px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .add-coupon {
            padding: 20px;
        }

        .coupon-table th,
        .coupon-table td {
            font-size: 14px;
            padding: 10px;
        }

        .coupon-table td a {
            font-size: 14px;
            padding: 6px 12px;
        }
    }

</style>
<body>
    <?php 
        include 'admin_header.php'; 
    ?>

    <section class="add-coupon">
        <h1 class="title">Add New Coupon</h1>
        <form action="" method="POST">
            <div class="inputBox">
                <input type="text" name="coupon_name" class="box" required placeholder="Enter coupon name">
                <input type="number" name="percentage" class="box" required placeholder="Enter coupon percentage">
            </div>
            <input type="submit" class="btn" value="Add Coupon" name="add_coupon">
        </form>
    </section>

    <section class="show-coupons">
        <h1 class="title">Available Coupons</h1>
        <table class="coupon-table">
            <thead>
                <tr>
                    <th>Coupon Name</th>
                    <th>Percentage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    // Fetch all the coupons from the database
                    $show_coupons = $conn->prepare("SELECT * FROM `coupon_table`");
                    $show_coupons->execute();

                    if ($show_coupons->rowCount() > 0) {
                        while ($fetch_coupon = $show_coupons->fetch(PDO::FETCH_ASSOC)) {
                ?>
                <tr>
                    <td><?= $fetch_coupon['coupon_name']; ?></td>
                    <td><?= $fetch_coupon['percentage']; ?>%</td>
                    <td>
                        <a href="admin_coupons.php?update=<?= $fetch_coupon['id']; ?>&percentage=<?= $fetch_coupon['percentage']; ?>" class="option-btn">Update</a>
                        <a href="admin_coupons.php?delete=<?= $fetch_coupon['id']; ?>" class="delete-btn" onclick="return confirm('Delete this coupon?');">Delete</a>
                    </td>
                </tr>
                <?php
                        }
                    } else {
                        echo '<tr><td colspan="3" class="empty">No coupons available.</td></tr>';
                    }
                ?>
            </tbody>
        </table>
    </section>

    <script src="js/script.js"></script>
</body>
</html>
