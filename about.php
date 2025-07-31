<?php
    @include 'config.php';
    session_start();
    $user_id = $_SESSION['user_id'];

    if(!isset($user_id)){
        header('location:login.php');
    };
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width", initial-scale=1.0>
        <title>about</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        <link rel="stylesheet" href="css/style.css"> 
    </head>
    <body>
        <?php
            include 'header.php';
        ?>
        <section class="about">
            <div class="row">
                <div class="box">
                    <img src="images/all.jpg" alt="">
                    <h3>why choose us?</h3>
                    <p>Your one-stop grocery destination. Unmatched variety, quality assurance, convenience, speedy delivery, exceptional service, competitive prices, and community engagement. Experience hassle-free shopping. Join us today!</p>
                    <a href="contact.php" class="btn">contact us</a>
                </div>
                <div class="box">
                    <img src="images/delivery.jpg" alt="">
                    <h3>what we provide</h3>
                    <p>At FreshMart, we offer a seamless payment system, ensuring secure transactions for your convenience. Our extensive product range includes fresh groceries and gourmet items. With our efficient delivery process, expect timely and reliable service. Shop confidently with FreshMart today!</p>
                    <a href="shop.php" class="btn">our shop</a>
                </div>
            </div>
        </section>
        <?php
            include 'footer.php';
        ?>



        <script src="js/script.js"></script>
    </body>
</html>  