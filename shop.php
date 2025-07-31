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
    $pid = $_POST['pid'];
    $p_name = $_POST['p_name'];
    $p_price = $_POST['p_price'];
    $p_image = $_POST['p_image'];

    // Sanitize inputs
    $pid = filter_var($pid, FILTER_SANITIZE_STRING);
    $p_name = filter_var($p_name, FILTER_SANITIZE_STRING);
    $p_price = filter_var($p_price, FILTER_SANITIZE_STRING);
    $p_image = filter_var($p_image, FILTER_SANITIZE_STRING);

    // Check if the product is already in the wishlist or cart
    $check_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
    $check_wishlist->execute([$p_name, $user_id]);

    $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
    $check_cart->execute([$p_name, $user_id]);

    if ($check_wishlist->rowCount() > 0) {
        $message[] = 'Already added to wishlist!';
    } else if ($check_cart->rowCount() > 0) {
        $message[] = 'Already added to cart!';
    } else {
        // Add product to wishlist
        $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
        $insert_wishlist->execute([$user_id, $pid, $p_name, $p_price, $p_image]);
        $message[] = 'Added to wishlist!';
    }
}

// Add to cart logic
if (isset($_POST['add_to_cart'])) {
    $pid = $_POST['pid'];
    $p_name = $_POST['p_name'];
    $p_price = $_POST['p_price'];
    $p_image = $_POST['p_image'];
    $p_qty = $_POST['p_qty'];

    // Sanitize inputs
    $pid = filter_var($pid, FILTER_SANITIZE_STRING);
    $p_name = filter_var($p_name, FILTER_SANITIZE_STRING);
    $p_price = filter_var($p_price, FILTER_SANITIZE_STRING);
    $p_image = filter_var($p_image, FILTER_SANITIZE_STRING);
    $p_qty = filter_var($p_qty, FILTER_SANITIZE_NUMBER_INT);

    // Check if the product is already in the cart
    $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
    $check_cart->execute([$p_name, $user_id]);

    if ($check_cart->rowCount() > 0) {
        $message[] = 'Already added to cart!';
    } else {
        // Get the total quantity of the product in the cart
        $select_cart_qty = $conn->prepare("SELECT SUM(quantity) AS total_quantity FROM `cart` WHERE pid = ? AND user_id = ?");
        $select_cart_qty->execute([$pid, $user_id]);
        $cart_qty = $select_cart_qty->fetch(PDO::FETCH_ASSOC)['total_quantity'] ?? 0;

        // Get the current stock from the products table
        $select_product = $conn->prepare("SELECT stock FROM `products` WHERE id = ?");
        $select_product->execute([$pid]);
        $product = $select_product->fetch(PDO::FETCH_ASSOC);
        $current_stock = $product['stock'];

        // Calculate available stock after considering the current cart quantity
        $available_stock = $current_stock;

        // Check if the available stock is sufficient for the requested quantity
        if ($available_stock >= $p_qty) {
            // Add product to cart
            $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
            $insert_cart->execute([$user_id, $pid, $p_name, $p_price, $p_qty, $p_image]);

            // Delete product from wishlist if it's there
            $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE pid = ? AND user_id = ?");
            $delete_wishlist->execute([$pid, $user_id]);

            // Update stock in the products table
            //$new_stock = $current_stock - $p_qty;
            //$update_stock = $conn->prepare("UPDATE `products` SET stock = ? WHERE id = ?");
            //$update_stock->execute([$new_stock, $pid]);

            $message[] = 'Added to cart! Wishlist item removed.';
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
    <title>Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
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
        color: #28a745;
    }

    .inventory-value.out-of-stock {
        color: #e74c3c;
    }
</style>
<body>
    <?php include 'header.php'; ?>
    <section class="p-catagory">
            <a href="catagory.php?catagory=fruits">fruits</a>
            <a href="catagory.php?catagory=vegetables">vegetables</a>
            <a href="catagory.php?catagory=meat">meat</a>
            <a href="catagory.php?catagory=fish">fish</a>
    </section>
    <section class="products">
        <h1 class="title">Latest Products</h1>
        <div class="box-container">

            <?php
            $select_products = $conn->prepare("SELECT * FROM `products`");
            $select_products->execute();
            if ($select_products->rowCount() > 0) {
                while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {

                    // Get the total quantity in the cart for this product
                    $cart_quantity = 0;
                    $select_cart_quantity = $conn->prepare("SELECT SUM(quantity) AS total_quantity FROM `cart` WHERE pid = ? AND user_id = ?");
                    $select_cart_quantity->execute([$fetch_products['id'], $user_id]);
                    $cart_quantity = $select_cart_quantity->fetch(PDO::FETCH_ASSOC)['total_quantity'] ?? 0;

                    // Calculate available stock after considering the cart quantity
                    $available_stock = $fetch_products['stock'] ;

                    // Show available stock + selected quantity
                    $available_stock_to_show = $available_stock ;

            ?>
            <form action="" method="POST" class="box">
                <div class="price"><span><?= $fetch_products['price']; ?></span>/-</div>
                <a href="view_page.php?pid=<?= $fetch_products['id']; ?>" class="fas fa-eye"></a>
                <img src="uploaded_img/<?= $fetch_products['image']; ?>" alt="">
                <div class="name"><?= $fetch_products['name']; ?></div>
                <input type="hidden" name="pid" value="<?= $fetch_products['id']; ?>">
                <input type="hidden" name="p_name" value="<?= $fetch_products['name']; ?>">
                <input type="hidden" name="p_price" value="<?= $fetch_products['price']; ?>">
                <input type="hidden" name="p_image" value="<?= $fetch_products['image']; ?>">

                <div class="inventory">
                    <span class="inventory-label">Available Stock:</span>
                    <span class="inventory-value <?= $available_stock == 0 ? 'out-of-stock' : 'in-stock'; ?>">
                        <?= $available_stock_to_show > 0 ? $available_stock_to_show : 'Out of stock'; ?>
                    </span>
                </div>

                <input type="number" min="1" max="<?= $available_stock_to_show; ?>" value="1" name="p_qty" class="qty" <?= $available_stock_to_show <= 0 ? 'disabled' : ''; ?>>
                <input type="submit" value="Add to Wishlist" class="option-btn" name="add_to_wishlist" <?= $available_stock_to_show <= 0 ? 'disabled' : ''; ?>>
                <input type="submit" value="Add to Cart" class="btn" name="add_to_cart" <?= $available_stock_to_show <= 0 ? 'disabled' : ''; ?>>
            </form>
            <?php
                }
            } else {
                echo '<p class="empty">No products added yet!</p>';
            }
            ?>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script src="js/script.js"></script>
</body>
</html>
