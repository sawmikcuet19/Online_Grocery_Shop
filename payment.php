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
        $pass = $_POST['pass'];
        $pass = filter_var($pass, FILTER_SANITIZE_STRING);

        $select = $conn->prepare("SELECT * FROM `payment` WHERE number = ? AND password = ? AND user_id = ?");
        $select->execute([$num, $pass, $user_id]);
        //$row = $select->fetch(PDO::FETCH_ASSOC);
        if($select->rowcount() > 0){
            //$message[] = 'proceed!';
            header('location:otp.php');
        }
        else{
            $message[] = 'incorrect number or password!';
        }
    
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width", initial-scale=1.0>
        <title>payment</title>

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
                <h3>payment now</h3>
                <input type="number" name="number" class="box" placeholder="enter your number" required >
                <input type="password" name="pass" class="box" placeholder="enter your pin" required >
                <input type="submit" value="pay bill" class="btn" name="submit">
            </form>
        </section>
    </body>
</html>