<?php
    @include 'config.php';
    session_start();
    $user_id = $_SESSION['user_id'];

    if(!isset($user_id)){
        header('location:login.php');
    };

    if(isset($_POST['order'])){
        $name = $_POST['name'];
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $number = $_POST['number'];
        $number = filter_var($number, FILTER_SANITIZE_STRING);
        $email = $_POST['email'];
        $email = filter_var($email, FILTER_SANITIZE_STRING);
        $method = $_POST['method'];
        $method = filter_var($method, FILTER_SANITIZE_STRING);
        //$address = 'house no.'. $_POST['house'] .', '. $_POST['street'] .', '. $_POST['local'] .', '. $_POST['city'] .', '. $_POST['country'] .' - '. $_POST['pin_code'];
        $address = 'house no.'. $_POST['house'] .', '. $_POST['street'] .', '. $_POST['local'] .', '. $_POST['city'] .', '. $_POST['country'];
        $address = filter_var($address, FILTER_SANITIZE_STRING);
        $placed_on = date('d-M-Y');

        $cart_total = 0;
        $cart_products[] = '';

        $cart_query = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
        $cart_query->execute([$user_id]);
        if($cart_query->rowCount() > 0){
            while($cart_item = $cart_query->fetch(PDO::FETCH_ASSOC)){
                $cart_products[] = $cart_item['name'].'( '.$cart_item['quantity'].' )';
                $sub_total = ($cart_item['price'] * $cart_item['quantity']);
                $cart_total += $sub_total;
            };
        };
        $total_product = implode(', ', $cart_products);
        $order_query = $conn->prepare("SELECT * FROM `orders` WHERE name = ? AND number = ? AND email = ? AND method = ? AND address = ? AND total_products = ? AND total_price = ?");
        $order_query->execute([$name, $number, $email, $method, $address, $total_product, $cart_total]);

        if($cart_total == 0){
            $message[] = 'your cart is empty!';
        }
        else if($order_query->rowCount() > 0){
            $message[] = 'order is already pressed!';
        }
        else{
            $ps = 'pending';
            $insert_order = $conn->prepare("INSERT INTO `orders` (user_id, name, number, email, method, address, total_products, total_price, placed_on, payment_status) VALUES(?,?,?,?,?,?,?,?,?,?)");
            $insert_order->execute([$user_id, $name, $number, $email, $method, $address, $total_product, $cart_total, $placed_on, $ps]);
            $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
            $delete_cart->execute([$user_id]);
            //$message[] = 'order placed successfully!';
            
            if($method == 'cash on delivery'){
                $message[] = 'order placed successfully!';
            }else{
                header('location:payment.php');
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width", initial-scale=1.0>
        <title>checkout</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        <link rel="stylesheet" href="css/style.css"> 
    </head>
    <body>
        <?php
            include 'header.php';
        ?>
        <section class="display-orders">
            <?php
                $cart_grand_total = 0;
                $select_cart_items =  $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
                $select_cart_items->execute([$user_id]);

                if($select_cart_items->rowCount() > 0){
                    while($fetch_cart_items = $select_cart_items->fetch(PDO::FETCH_ASSOC)){
                        $cart_total_price = ($fetch_cart_items['price'] * $fetch_cart_items['quantity']);
                        $cart_grand_total += $cart_total_price;
            ?>
            <p> <?= $fetch_cart_items['name']; ?> <span>(<?= $fetch_cart_items['price'] .' /- x '. $fetch_cart_items['quantity'] ?>)</span> </p>
            <?php
                 }
                }else{
                    echo '<p class="empty">your cart is empty!</p>';
                }
            ?>
            <div class="grand-total">grand total : <span><?= $cart_grand_total; ?>/-</span></div>
        </section>

        <section class="checkout-orders">
            <form action="" method="POST">
                <h3>place your order</h3>
                <div class="flex">
                    <div class="inputBox">
                        <span>your name :</span>
                        <input type="text" name="name" placeholder="enter your name" class="box" required>
                    </div>
                    <div class="inputBox">
                        <span>your number :</span>
                        <input type="number" name="number" placeholder="enter your phone number/Account" class="box" required>
                    </div>
                    <div class="inputBox">
                        <span>your email :</span>
                        <input type="email" name="email" placeholder="enter your email" class="box" required>
                    </div>
                    <div class="inputBox">
                        <span>payment method :</span>
                        <select name="method" class="box" required>
                            <option value="cash on delivery">cash on delivery</option>
                            <option value="credit card">credit card</option>
                            <option value="Bkash">Bkash</option>
                            <option value="Rocket">Rocket</option>
                        </select>
                    </div>
                    <div class="inputBox">
                        <span>address line 01 :</span>
                        <input type="text" name="house" placeholder="enter house number" class="box" required>
                    </div>
                    <div class="inputBox">
                        <span>address line 02 :</span>
                        <input type="text" name="street" placeholder="enter street name/number" class="box" required>
                    </div>
                    <div class="inputBox">
                        <span>local area name :</span>
                        <input type="text" name="local" placeholder="enter local area name" class="box" required>
                    </div>
                    <div class="inputBox">
                        <span>city name :</span>
                        <input type="text" name="city" placeholder="enter city name" class="box" required>
                    </div>
                    <div class="inputBox">
                        <span>country :</span>
                        <input type="text" name="country" placeholder="enter country name" class="box" required>
                    </div>
                    <!--<div class="inputBox">
                        <span>transaction ID :</span>
                        <input type="number" min="0" name="pin_code" placeholder="input tnx" class="box"required>
                    </div>  -->

                    <input type="submit" name="order" class="btn <?= ($cart_grand_total > 1)?'':'disabled' ?>" value="place order">
                </div>
            </form>
        </section>
        <?php
            include 'footer.php';
        ?>



        <script src="js/script.js"></script>
    </body>
</html>  