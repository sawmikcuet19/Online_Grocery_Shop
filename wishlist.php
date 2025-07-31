<?php
    @include 'config.php';
    session_start();
    $user_id = $_SESSION['user_id'];

    if (!isset($user_id)) {
        header('location:login.php');
    }

    // Handle adding products to the cart
    if (isset($_POST['add_to_cart'])) {
        $pid = $_POST['pid'];
        $pid = filter_var($pid, FILTER_SANITIZE_STRING);
        $p_name = $_POST['p_name'];
        $p_name = filter_var($p_name, FILTER_SANITIZE_STRING);
        $p_price = $_POST['p_price'];
        $p_price = filter_var($p_price, FILTER_SANITIZE_STRING);
        $p_image = $_POST['p_image'];
        $p_image = filter_var($p_image, FILTER_SANITIZE_STRING);
        $p_qty = $_POST['p_qty'];
        $p_qty = filter_var($p_qty, FILTER_SANITIZE_STRING);

        // Fetch stock from products table
        $check_stock = $conn->prepare("SELECT stock FROM `products` WHERE id = ?");
        $check_stock->execute([$pid]);
        $product = $check_stock->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $stock = $product['stock'];
            if ($p_qty > $stock) {
                $message[] = 'Quantity exceeds available stock!';
            } else {
                // Check if product is already in cart
                $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE pid = ? AND user_id = ?");
                $check_cart_numbers->execute([$pid, $user_id]);

                if ($check_cart_numbers->rowCount() > 0) {
                    $message[] = 'Product is already in the cart!';
                } else {
                    // Remove product from wishlist
                    $check_wishlist_numbers = $conn->prepare("DELETE FROM `wishlist` WHERE pid = ? AND user_id = ?");
                    $check_wishlist_numbers->execute([$pid, $user_id]);

                    // Add to cart
                    $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
                    $insert_cart->execute([$user_id, $pid, $p_name, $p_price, $p_qty, $p_image]);
                    $message[] = 'Product added to cart!';
                }
            }
        } else {
            $message[] = 'Product not found!';
        }
    }

    // Handle deleting product from wishlist
    if (isset($_GET['delete'])) {
        $delete_id = $_GET['delete'];
        $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE id = ?");
        $delete_wishlist_item->execute([$delete_id]);
        header('location:wishlist.php');
    }

    // Handle deleting all items from wishlist
    if (isset($_GET['delete_all'])) {
        $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE user_id = ?");
        $delete_wishlist_item->execute([$user_id]);
        header('location:wishlist.php');
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Wishlist</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        <link rel="stylesheet" href="css/style.css"> 
        <style>
            /* Styling for the inventory section */
            .inventory {
                font-size: 1.5rem;
                margin: 15px 0;
                padding: 12px;
                background-color: #f4f4f4;
                border: 1px solid #ddd;
                border-radius: 8px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                font-family: Arial, sans-serif;
            }

            /* Label for the inventory status */
            .inventory-label {
                font-weight: bold;
                color: #555;
                margin-right: 10px;  /* Adding space between label and value */
            }

            /* Value for the inventory status (in-stock or out-of-stock) */
            .inventory-value {
                font-weight: bold;
                font-size: 1.2rem;
            }

            /* Styling for in-stock value */
            .inventory-value.in-stock {
                color: #28a745; /* Green for stock available */
            }

            /* Styling for out-of-stock value */
            .inventory-value.out-of-stock {
                color: #e74c3c; /* Red for out of stock */
            }

            /* Styling for the quantity input field */
            .p_qty {
                width: 80px; /* Width for the input */
                padding: 8px 12px;
                font-size: 1.2rem;
                border: 1px solid #ccc;
                border-radius: 5px;
                margin-top: 10px; /* Add space between inventory and quantity */
                margin-left: 10px; /* Adds a bit of space between the two elements */
                text-align: center;
            }

            /* For spacing between input and button */
            .box form {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .box .btn {
                margin-top: 20px;  /* Adds space between input and the button */
            }


        </style>
    </head>
    <body>
        <?php include 'header.php'; ?>

        <section class="wishlist">
            <h1 class="title">Products Added to Wishlist</h1>
            <div class="box-container">
                <?php
                    $grand_total = 0;
                    $select_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ?");
                    $select_wishlist->execute([$user_id]);
                    if ($select_wishlist->rowCount() > 0) {
                        while ($fetch_wishlist = $select_wishlist->fetch(PDO::FETCH_ASSOC)) {
                            $pid = $fetch_wishlist['pid'];
                            $p_name = $fetch_wishlist['name'];
                            $p_price = $fetch_wishlist['price'];
                            $p_image = $fetch_wishlist['image'];

                            // Fetch stock for the product
                            $check_stock = $conn->prepare("SELECT stock FROM `products` WHERE id = ?");
                            $check_stock->execute([$pid]);
                            $product_stock = $check_stock->fetch(PDO::FETCH_ASSOC);
                            $stock = $product_stock['stock'];
                ?>
                <form action="" method="POST" class="box">
                    <a href="wishlist.php?delete=<?= $fetch_wishlist['id']; ?>" class="fas fa-times" onclick="return confirm('Delete this from wishlist?');"></a>
                    <a href="view_page.php?pid=<?= $fetch_wishlist['pid']; ?>" class="fas fa-eye"></a>
                    <img src="uploaded_img/<?= $fetch_wishlist['image']; ?>" alt="">
                    <div class="name"><?= $fetch_wishlist['name']; ?></div>
                    <div class="price"><?= $fetch_wishlist['price']; ?>/-</div>

                    <!-- Inventory Display -->
                    <div class="inventory">
                        <span class="inventory-label">Inventory Number:</span>
                        <span class="inventory-value <?= $stock == 0 ? 'out-of-stock' : 'in-stock'; ?>">
                            <?= htmlspecialchars($stock); ?>
                        </span>
                    </div>

                    <!-- Quantity input with stock limit -->
                    <input type="number" min="1" name="p_qty" class="p_qty" value="1" max="<?= $stock; ?>" required>
                    <input type="hidden" name="pid" value="<?= $fetch_wishlist['pid']; ?>">
                    <input type="hidden" name="p_name" value="<?= $fetch_wishlist['name']; ?>">
                    <input type="hidden" name="p_price" value="<?= $fetch_wishlist['price']; ?>">
                    <input type="hidden" name="p_image" value="<?= $fetch_wishlist['image']; ?>">
                    <input type="submit" value="Add to cart" name="add_to_cart" class="btn">
                </form>
                <?php
                        $grand_total += $fetch_wishlist['price'];
                    }
                } else {
                    echo '<p class="empty">Your wishlist is empty!</p>';
                }
                ?>
            </div>
            <div class="wishlist-total">
                <p>Grand Total: <span><?= $grand_total; ?>/-</span></p>
                <a href="shop.php" class="option-btn">Continue Shopping</a>
                <a href="wishlist.php?delete_all" class="delete-btn <?= ($grand_total > 1) ? '' : 'disabled' ?>">Delete All</a>
            </div>
        </section>

        <?php include 'footer.php'; ?>
        <script src="js/script.js"></script>
    </body>
</html>
