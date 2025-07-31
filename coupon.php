<?php
    @include 'config.php';
    session_start();
    $user_id = $_SESSION['user_id'];

    if(!isset($user_id)){
        header('location:login.php');
    };

    // Fetch coupon names from the coupon_table
    $select_coupons = $conn->prepare("SELECT coupon_name FROM `coupon_table`");
    $select_coupons->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>

        table {
            width: 80%;
            margin: 30px auto;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        th, td {
            padding: 12px 20px;
            text-align: center;
            font-size: 3rem;
            color: #555;
        }

        th {
            background-color: #4CAF50;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        td {
            background-color: #f9f9f9;
        }

        tr:nth-child(even) {
            background-color: #f1f1f1;
        }

        tr:hover {
            background-color: #e0f7e8;
        }

        tbody tr td {
            font-weight: 500;
        }

        @media (max-width: 768px) {
            table {
                width: 90%;
            }
            .title {
                font-size: 2rem;
            }
        }

        footer {
            text-align: center;
            background-color: #4CAF50;
            color: white;
            padding: 10px 0;
            position: relative;
            bottom: 0;
            width: 100%;
            font-size: 1rem;
        }

    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="title">
        <h1>Available Coupons</h1>
    </div>

    <table>
        <thead>
            <tr>
                <th>Coupon Name</th>
            </tr>
        </thead>
        <tbody>
            <?php
                if($select_coupons->rowCount() > 0) {
                    while($fetch_coupons = $select_coupons->fetch(PDO::FETCH_ASSOC)){
                        echo "<tr><td>{$fetch_coupons['coupon_name']}</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='1'>No coupons available</td></tr>";
                }
            ?>
        </tbody>
    </table>

    <?php include 'footer.php'; ?>

    <script src="js/script.js"></script>
</body>
</html>
