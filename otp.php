<?php
    @include 'config.php';
    session_start();
    $user_id = $_SESSION['user_id'];

    if(!isset($user_id)){
        header('location:login.php');
    };

    if(isset($_POST['submit'])){
        $num = $_POST['number'];
        $num = filter_var($num, FILTER_SANITIZE_STRING);
        $otp = $_POST['otp'];
        $otp = filter_var($otp, FILTER_SANITIZE_STRING);

        $select = $conn->prepare("SELECT * FROM `payment` WHERE number = ? AND otp = ? AND user_id = ?");
        $select->execute([$num, $otp, $user_id]);
        $row = $select->fetch(PDO::FETCH_ASSOC);
        if($select->rowcount() > 0){
            //$message[] = 'proceed!';
            header('location:orders.php');
        }
        else{
            $message[] = 'incorrect number or otp!';
        }
    
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width", initial-scale=1.0>
        <title>otp</title>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        <link rel="stylesheet" href="css/components.css"> 
    </head>
    <body>
        <?php
            if(isset($message)){
                foreach($message as $message){
                    echo '
                    <div class="message">
                        <span>'.$message.'</span>
                        <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
                    </div>
                    ';
                }
            }
        ?>
        <section class="form-container">
            <form action="" enctype="multipart/form-data" method="POST">
                <h3>confirm otp</h3>
                <input type="number" name="number" class="box" placeholder="enter your number" required >
                <input type="password" name="otp" class="box" placeholder="enter your otp" required >
                <input type="submit" value="pay bill" class="btn" name="submit">
            </form>
        </section>
    </body>
</html>