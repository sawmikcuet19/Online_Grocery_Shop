<?php
@include 'config.php';
session_start();
$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
    header('location:login.php');
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE id = ?");
    $delete_cart_item->execute([$delete_id]);
    header('location:cart.php');
}

if (isset($_GET['delete_all'])) {
    $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
    $delete_cart_item->execute([$user_id]);
    header('location:cart.php');
}

if (isset($_POST['update_qty'])) {
    $cart_id = $_POST['cart_id'];
    $p_qty = $_POST['p_qty'];
    $p_qty = filter_var($p_qty, FILTER_SANITIZE_STRING);

    // Get stock of the product from the products table
    $cart_pid = $_POST['cart_pid']; // Product ID from cart
    $select_product = $conn->prepare("SELECT stock FROM `products` WHERE id = ?");
    $select_product->execute([$cart_pid]);
    $product = $select_product->fetch(PDO::FETCH_ASSOC);
    $stock = $product['stock']; // Available stock of the product

    if ($p_qty > $stock) {
        // Ensure $message is an array before appending
        if (!isset($message)) {
            $message = [];
        }
        $message[] = 'Quantity exceeds stock available for ' . $product['name'] . '!';
    } else {
        $update_qty = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE id = ?");
        $update_qty->execute([$p_qty, $cart_id]);
        // Ensure $message is an array before appending
        if (!isset($message)) {
            $message = [];
        }
        $message[] = 'Cart quantity updated!';
    }
}

$cart_valid = true;  // Flag to check if all cart quantities are valid

// Check if cart quantities are valid (do this only once per page load)
$select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
$select_cart->execute([$user_id]);
while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
    $product_id = $fetch_cart['pid'];
    $select_product = $conn->prepare("SELECT stock FROM `products` WHERE id = ?");
    $select_product->execute([$product_id]);
    $product = $select_product->fetch(PDO::FETCH_ASSOC);
    $stock = $product['stock']; // Get the stock of the product

    // Check if cart quantity exceeds stock
    if ($fetch_cart['quantity'] > $stock) {
        $cart_valid = false;  // Set flag to false if any cart quantity exceeds stock
        break;  // No need to continue checking other products
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<style>
    .inventory {
        font-size: 1.5rem;
        padding: 12px;
        margin: 20px 0;
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: Arial, sans-serif;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .inventory-label {
        font-weight: bold;
        color: #555;
        margin-right: 15px;
    }

    .inventory-value {
        font-weight: bold;
        font-size: 1.3rem;
        padding: 6px 10px;
        border-radius: 5px;
        display: inline-block;
    }

    .inventory-value.in-stock {
        color: #fff;
        background-color: #28a745;
        border: 1px solid #28a745;
    }

    .inventory-value.out-of-stock {
        color: #fff;
        background-color: #e74c3c;
        border: 1px solid #e74c3c;
    }

    .inventory-value:hover {
        opacity: 0.8;
        cursor: pointer;
    }

    .error-message {
        color: red;
        font-size: 1.5rem;
    }

    .btn.disabled {
        pointer-events: none;
        opacity: 0.5;
    }
</style>
<body>
    <?php include 'header.php'; ?>
    <section class="shopping-cart">
        <h1 class="title">Products in your cart</h1>

        <?php
        // Display any messages if any
        if (isset($message) && is_array($message)) {
            foreach ($message as $msg) {
                echo "<p class='message'>$msg</p>";
            }
        }
        ?>

        <div class="box-container">
            <?php
            $grand_total = 0;
            $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $select_cart->execute([$user_id]);
            if ($select_cart->rowCount() > 0) {
                while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                    // Get stock of the product from the products table
                    $product_id = $fetch_cart['pid'];
                    $select_product = $conn->prepare("SELECT stock FROM `products` WHERE id = ?");
                    $select_product->execute([$product_id]);
                    $product = $select_product->fetch(PDO::FETCH_ASSOC);
                    $stock = $product['stock']; // Get the stock of the product
            ?>
            <form action="" method="POST" class="box">
                <a href="cart.php?delete=<?= $fetch_cart['id']; ?>" class="fas fa-times" onclick="return confirm('Delete this from cart?');"></a>
                <a href="view_page.php?pid=<?= $fetch_cart['pid']; ?>" class="fas fa-eye"></a>
                <img src="uploaded_img/<?= $fetch_cart['image']; ?>" alt="">
                <div class="name"><?= $fetch_cart['name']; ?></div>
                <div class="price"><?= $fetch_cart['price']; ?>/-</div>

                <div class="inventory">
                    <span class="inventory-label">Stock available:</span>
                    <span class="inventory-value <?= $stock == 0 ? 'out-of-stock' : 'in-stock'; ?>">
                        <?= htmlspecialchars($stock); ?>
                    </span>
                </div>

                <div class="flex-btn">
                    <input type="number" min="1" value="<?= $fetch_cart['quantity'] ?>" name="p_qty" class="p_qty" max="<?= $stock; ?>" required>
                    <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
                    <input type="hidden" name="cart_pid" value="<?= $fetch_cart['pid']; ?>">
                    <input type="submit" value="Update" name="update_qty" class="option-btn">
                </div>
                <div class="sub-total">Sub total: <span><?= $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']); ?>/-</span></div>
            </form>
            <?php
                $grand_total += $sub_total;
                }
            } else {
                echo '<p class="empty">Your cart is empty!</p>';
            }
            ?>
        </div>

        <div class="cart-total">
            <p>Grand total: <span><?= $grand_total; ?>/-</span></p>

            <!-- Show message and disable buttons if cart is invalid -->
            <?php if ($cart_valid): ?>
                <a href="shop.php" class="option-btn">Continue shopping</a>
                <a href="cart.php?delete_all" class="delete-btn <?= ($grand_total > 1) ? '' : 'disabled' ?>">Delete all</a>
                <a href="checkout.php" class="btn <?= ($grand_total > 1) ? '' : 'disabled' ?>" id="checkout-btn">Proceed to checkout</a>
            <?php else: ?>
                <p class="error-message">One or more products exceed the available stock. Please update your cart.</p>
                <a href="shop.php" class="option-btn">Continue shopping</a>
                <a href="cart.php" class="btn disabled">Proceed to checkout</a>
            <?php endif; ?>
        </div>
    </section>
    <?php include 'footer.php'; ?>

    <script src="js/script.js"></script>
    <script>
        document.getElementById('checkout-btn').addEventListener('click', function(event) {
            // Check if cart is valid
            <?php if (!$cart_valid): ?>
                event.preventDefault();  // Prevent the checkout if cart is invalid
                alert('One or more products exceed the available stock. Please update your cart.');
            <?php endif; ?>
        });
    </script>
</body>
</html>
