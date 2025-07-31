<?php
@include 'config.php';
session_start();
$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
    header('location:login.php');
    exit;
}

// Add to wishlist logic
if (isset($_POST['add_to_wishlist'])) {
    $pid = filter_var($_POST['pid'], FILTER_SANITIZE_STRING);
    $p_name = filter_var($_POST['p_name'], FILTER_SANITIZE_STRING);
    $p_price = filter_var($_POST['p_price'], FILTER_SANITIZE_STRING);
    $p_image = filter_var($_POST['p_image'], FILTER_SANITIZE_STRING);

    // Check if the product is already in wishlist or cart
    $check_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
    $check_wishlist->execute([$p_name, $user_id]);
    $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
    $check_cart->execute([$p_name, $user_id]);

    if ($check_wishlist->rowCount() > 0) {
        $message[] = 'Already added to wishlist!';
    } elseif ($check_cart->rowCount() > 0) {
        $message[] = 'Already added to cart!';
    } else {
        $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
        $insert_wishlist->execute([$user_id, $pid, $p_name, $p_price, $p_image]);
        $message[] = 'Added to wishlist!';
    }
}

// Add to cart logic
if (isset($_POST['add_to_cart'])) {
    $pid = filter_var($_POST['pid'], FILTER_SANITIZE_STRING);
    $p_name = filter_var($_POST['p_name'], FILTER_SANITIZE_STRING);
    $p_price = filter_var($_POST['p_price'], FILTER_SANITIZE_STRING);
    $p_image = filter_var($_POST['p_image'], FILTER_SANITIZE_STRING);
    $p_qty = filter_var($_POST['p_qty'], FILTER_SANITIZE_NUMBER_INT);

    // Check if the product is already in cart
    $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
    $check_cart->execute([$p_name, $user_id]);

    if ($check_cart->rowCount() > 0) {
        $message[] = 'Already added to cart!';
    } else {
        // Get the available stock for the product
        $select_product = $conn->prepare("SELECT stock FROM `products` WHERE id = ?");
        $select_product->execute([$pid]);
        $product = $select_product->fetch(PDO::FETCH_ASSOC);
        $available_stock = $product['stock']; // Actual stock

        if ($available_stock >= $p_qty) {
            // Add product to cart without updating the stock
            $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
            $insert_cart->execute([$user_id, $pid, $p_name, $p_price, $p_qty, $p_image]);

            // Check if the product is in the wishlist and delete it if it's there
            $delete_from_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE name = ? AND user_id = ?");
            $delete_from_wishlist->execute([$p_name, $user_id]);

            $message[] = 'Added to cart!';
        } else {
            $message[] = 'Not enough stock available!';
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
    <title>Quick View</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .inventory {
            font-size: 1.5rem;
            margin: 10px 0;
            padding: 10px;
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: inline-block;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            font-family: Arial, sans-serif;
        }

        .inventory-label {
            font-weight: bold;
            color: #555;
            margin-right: 5px;
        }

        .inventory-value {
            font-weight: bold;
            font-size: 1.2rem;
        }

        .inventory-value.in-stock {
            color: #28a745; /* Green for stock available */
        }

        .inventory-value.out-of-stock {
            color: #e74c3c; /* Red for no stock */
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="quick-view">
        <h1 class="title">Quick View</h1>
        <?php
            $pid = $_GET['pid'];
            $select_products = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
            $select_products->execute([$pid]);

            if ($select_products->rowCount() > 0) {
                while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
                    // Get the original stock of the product
                    $original_stock = $fetch_products['stock']; // Total stock (original stock)
        ?>
        <form action="" class="box" method="POST">
            <div class="price"><span><?= $fetch_products['price']; ?></span>/-</div>
            <img src="uploaded_img/<?= $fetch_products['image']; ?>" alt="Product Image">
            <div class="name"><?= $fetch_products['name']; ?></div>
            <div class="details"><?= $fetch_products['details']; ?></div>
            <input type="hidden" name="pid" value="<?= $fetch_products['id']; ?>">
            <input type="hidden" name="p_name" value="<?= $fetch_products['name']; ?>">
            <input type="hidden" name="p_price" value="<?= $fetch_products['price']; ?>">
            <input type="hidden" name="p_image" value="<?= $fetch_products['image']; ?>">

            <!-- Inventory display showing original stock -->
            <div class="inventory">
                <span class="inventory-label">Inventory Available :</span>
                <span class="inventory-value <?= $original_stock <= 0 ? 'out-of-stock' : 'in-stock'; ?>">
                    <?= $original_stock > 0 ? $original_stock : 'Out of stock'; ?>
                </span>
            </div>

            <!-- Max quantity input based on original stock (no need to subtract cart quantity) -->
            <input type="number" min="1" max="<?= $original_stock; ?>" value="1" name="p_qty" class="qty" <?= ($original_stock) <= 0 ? 'disabled' : ''; ?>>

            <!-- Buttons to add to wishlist or cart -->
            <input type="submit" value="Add to Wishlist" class="option-btn" name="add_to_wishlist" <?= $original_stock <= 0 ? 'disabled' : ''; ?>>
            <input type="submit" value="Add to Cart" class="btn" name="add_to_cart" <?= $original_stock <= 0 ? 'disabled' : ''; ?>>
        </form>
        <?php
                }
            } else {
                echo '<p class="empty">No products found!</p>';
            }
        ?>
    </section>

    <?php include 'footer.php'; ?>

    <script src="js/script.js"></script>
</body>
</html>
