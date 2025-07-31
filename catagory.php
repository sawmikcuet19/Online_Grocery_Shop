<?php
@include 'config.php';
session_start();
$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
    header('location:login.php');
};

if(isset($_POST['add_to_wishlist'])){
    $pid = $_POST['pid'];
    $pid = filter_var($pid, FILTER_SANITIZE_STRING);
    $p_name = $_POST['p_name'];
    $p_name = filter_var($p_name, FILTER_SANITIZE_STRING);
    $p_price = $_POST['p_price'];
    $p_price = filter_var($p_price, FILTER_SANITIZE_STRING);
    $p_image = $_POST['p_image'];
    $p_image = filter_var($p_image, FILTER_SANITIZE_STRING);

    // Check if product is already in wishlist or cart
    $check_wishlist_numbers = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
    $check_wishlist_numbers->execute([$p_name, $user_id]);
    $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
    $check_cart_numbers->execute([$p_name, $user_id]);

    if($check_wishlist_numbers->rowCount() > 0){
        $message[] = 'already added to wishlist!';
    }
    else if($check_cart_numbers->rowCount() > 0){
        $message[] = 'already added to cart!';
    }
    else{
        // Insert into wishlist
        $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
        $insert_wishlist->execute([$user_id, $pid, $p_name, $p_price, $p_image]);
        $message[] = 'added to wishlist!';
    }
}

if(isset($_POST['add_to_cart'])){
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

    // Check if product is already in cart
    $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
    $check_cart_numbers->execute([$p_name, $user_id]);

    if($check_cart_numbers->rowCount() > 0){
        $message[] = 'already added to cart!';
    }
    else{
        // Remove product from wishlist if it exists
        $delete_from_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE name = ? AND user_id = ?");
        $delete_from_wishlist->execute([$p_name, $user_id]);

        // Insert into cart
        $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
        $insert_cart->execute([$user_id, $pid, $p_name, $p_price, $p_qty, $p_image]);
        $message[] = 'added to cart!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category</title>
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
        color: #28a745; /* Green for stock available */
    }

    .inventory-value.out-of-stock {
        color: #e74c3c; /* Red for no stock */
    }
</style>
<body>
    <?php
        include 'header.php';
    ?>
    <section class="products">
        <h1 class="title">Product Categories</h1>
        <div class="box-container">
            <?php
                $catagory_name = $_GET['catagory'];
                $select_products = $conn->prepare("SELECT * FROM `products` WHERE catagory = ?");
                $select_products->execute([$catagory_name]);
                if($select_products->rowCount() > 0){
                    while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){
                        $available_stock = $fetch_products['stock']; // Get available stock
            ?>
            <form action="" class="box" method="POST">
                <div class="price"><span><?= $fetch_products['price']; ?></span>/-</div>
                <a href="view_page.php?pid=<?= $fetch_products['id']; ?>" class="fas fa-eye"></a>
                <img src="uploaded_img/<?= $fetch_products['image']; ?>" alt="">
                <div class="name"><?= $fetch_products['name']; ?></div>
                <input type="hidden" name="pid" value="<?= $fetch_products['id']; ?>">
                <input type="hidden" name="p_name" value="<?= $fetch_products['name']; ?>">
                <input type="hidden" name="p_price" value="<?= $fetch_products['price']; ?>">
                <input type="hidden" name="p_image" value="<?= $fetch_products['image']; ?>">
                
                <!-- Inventory Display -->
                <div class="inventory">
                    <span class="inventory-label">Inventory Number:</span>
                    <span class="inventory-value <?= $available_stock == 0 ? 'out-of-stock' : 'in-stock'; ?>">
                        <?= htmlspecialchars($available_stock); ?>
                    </span>
                </div>
                
                <!-- Quantity Input with Max set to available stock -->
                <input type="number" min="1" max="<?= $available_stock; ?>" value="1" name="p_qty" class="qty" <?= $available_stock == 0 ? 'disabled' : ''; ?>>
                
                <!-- Buttons to Add to Wishlist and Cart -->
                <input type="submit" value="Add to Wishlist" class="option-btn" name="add_to_wishlist" <?= $available_stock == 0 ? 'disabled' : ''; ?>>
                <input type="submit" value="Add to Cart" class="btn" name="add_to_cart" <?= $available_stock == 0 ? 'disabled' : ''; ?>>
            </form>
            <?php
                    }
                } else {
                    echo '<p class="empty">No products available!</p>';
                }
            ?>
        </div>
    </section>
    
    <?php
        include 'footer.php';
    ?>
    
    <script src="js/script.js"></script>
</body>
</html>
