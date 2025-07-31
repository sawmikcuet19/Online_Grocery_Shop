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

        $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
        $check_cart_numbers->execute([$p_name, $user_id]);
        

        if($check_cart_numbers->rowCount() > 0){
            $message[] = 'already added to cart!';
        }
        else{
            $check_wishlist_numbers = $conn->prepare("DELETE FROM `wishlist` WHERE name = ? AND user_id = ?");
            $check_wishlist_numbers->execute([$p_name, $user_id]);
            

            if($check_wishlist_numbers->rowCount() > 0){
                $delete_wishlist_ = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
                $delete_wishlist_->execute([$p_name, $user_id]);
            }
            $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
            $insert_cart->execute([$user_id, $pid, $p_name, $p_price,$p_qty, $p_image]);
            $message[] = 'added to cart!';
        }
    }
    
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width", initial-scale=1.0>
        <title>home page</title>
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

        <div class="home-bg">
            <section class="home">
                <div class="content">
                    <span>don't panic, go organize</span>
                    <h3>Reach A Healthier You With Organic Foods</h3>
                    <p>lorem ttbgetbi tbibneibir etbibeiiieii iueiubibiu uurrr uuuuthhir hhtigfie ietitggnbi ieubiibib ibibiybi erfbvb</p>
                    <a href="about.php" class="btn">about us</a>
                </div>
            </section>
        </div>

        <section class="home-catagory">
            <h1 class="title">shop by category</h1>
            <div class="box-container">
                <div class="box">
                    <img src="images/fruit.png" alt="">
                    <h3>fruits</h3>
                    <p>lorem ttbgetbi tbibneibir etbibeiiieii iueiubibiu uurrr uuuuthhir hhtigfie</p>
                    <a href="catagory.php?catagory=fruits" class="btn">fruits</a>
                </div>
                <div class="box">
                    <img src="images/meats.png" alt="">
                    <h3>meat</h3>
                    <p>lorem ttbgetbi tbibneibir etbibeiiieii iueiubibiu uurrr uuuuthhir hhtigfie ietitggnbi ieubiibib ibibiybi erfbvb</p>
                    <a href="catagory.php?catagory=meat" class="btn">meat</a>
                </div>
                <div class="box">
                    <img src="images/fresh_vegitable.jpg" alt="">
                    <h3>vegetables</h3>
                    <p>lorem ttbgetbi tbibneibir etbibeiiieii iueiubibiu uurrr uuuuthhir hhtigfie ietitggnbi ieubiibib ibibiybi erfbvb</p>
                    <a href="catagory.php?catagory=vegetables" class="btn">vegetables</a>
                </div>
                <div class="box">
                    <img src="images/istockphoto-1181148320-612x612.jpg" alt="">
                    <h3>fish</h3>
                    <p>lorem ttbgetbi tbibneibir etbibeiiieii iueiubibiu uurrr uuuuthhir hhtigfie ietitggnbi ieubiibib ibibiybi erfbvb</p>
                    <a href="catagory.php?catagory=fish" class="btn">fish</a>
                </div>
            </div>
        </section>
        <section class="products">
            <h1 class="title">latest products</h1>
            <div class="box-container">
                
                <?php
                    $select_products = $conn->prepare("SELECT * FROM `products` LIMIT 6");
                    $select_products->execute();
                    if($select_products-> rowCount() > 0){
                        while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){
                            
                       
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
                    <div class="inventory">
                        <span class="inventory-label">Inventory Number:</span>
                        <span class="inventory-value <?= $fetch_products['stock'] == 0 ? 'out-of-stock' : 'in-stock'; ?>">
                            <?= htmlspecialchars($fetch_products['stock']); ?>
                        </span>
                    </div>
                    <input type="number" min="1" max="<?= $fetch_products['stock']; ?>" value="1" name="p_qty" class="qty" <?= $fetch_products['stock'] == 0 ? 'disabled' : ''; ?>>
                    <input type="submit" value="add to wishlist" class="option-btn" name="add_to_wishlist" <?= $fetch_products['stock'] == 0 ? 'disabled' : ''; ?>>
                    <input type="submit" value="add to cart" class="btn" name="add_to_cart" <?= $fetch_products['stock'] == 0 ? 'disabled' : ''; ?>>
                </form>
                <?php
                     }
                    }else{
                        echo '<p class="empty">no products added yet!</p>';
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