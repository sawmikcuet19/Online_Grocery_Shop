<?php
    @include 'config.php';
    session_start();
    $admin_id = $_SESSION['admin_id'];

    if (!isset($admin_id)) {
        header('location:login.php');
        exit();
    }

    if (isset($_GET['delete'])) {
        $delete_id = $_GET['delete'];
        // Delete user ratings first
        $delete_ratings = $conn->prepare("DELETE FROM `ratings` WHERE user_id = ?");
        $delete_ratings->execute([$delete_id]);
        // Delete user account
        $delete_users = $conn->prepare("DELETE FROM `users` WHERE id = ?");
        $delete_users->execute([$delete_id]);
        header('location:admin_users.php');
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Ratings</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        <link rel="stylesheet" href="css/admin_style.css">
        <style>
            .stars {
                color: #f39c12; /* Gold color for all stars */
                display: inline-block;
                font-size: 1.2rem;
            }
            .stars .fa-star,
            .stars .fa-star-half-alt,
            .stars .fa-star-empty {
                opacity: 0.3; /* Default opacity for empty stars */
            }
            .stars .filled {
                opacity: 1; /* Fully visible for filled stars */
            }
            .stars .half {
                opacity: 1; /* Fully visible for half stars */
            }
            .rating-value {
                font-weight: bold;
                margin-left: 10px;
                color: #333;
            }
        </style>
    </head>
    <body>
        <?php 
            include 'admin_header.php'; 
        ?>
        <section class="user-accounts">
            <h1 class="title">User Accounts</h1>
            <div class="box-container">
                <?php
                    // Fetch all users
                    $select_users = $conn->prepare("SELECT * FROM `users`");
                    $select_users->execute();
                    while ($fetch_users = $select_users->fetch(PDO::FETCH_ASSOC)) {
                        // Fetch user rating
                        $select_ratings = $conn->prepare("SELECT rating FROM `ratings` WHERE user_id = ?");
                        $select_ratings->execute([$fetch_users['id']]);
                        $fetch_rating = $select_ratings->fetch(PDO::FETCH_ASSOC);
                        $user_rating = $fetch_rating['rating'] ?? 0;

                        // Calculate star ratings (full, half, empty)
                        $full_stars = floor($user_rating);
                        $half_star = ($user_rating - $full_stars >= 0.5) ? 1 : 0;
                        $empty_stars = 5 - $full_stars - $half_star;
                ?>
                <div class="box" style="<?php if ($fetch_users['id'] == $admin_id) {echo 'display:none'; }; ?>">
                    <img src="uploaded_img/<?= htmlspecialchars($fetch_users['image']); ?>" alt="">
                    <p>User ID: <span><?= htmlspecialchars($fetch_users['id']); ?></span></p>
                    <p>Username: <span><?= htmlspecialchars($fetch_users['name']); ?></span></p>
                    <p>Email: <span><?= htmlspecialchars($fetch_users['email']); ?></span></p>
                    <p>User Type: <span style="color:<?php if ($fetch_users['user_type'] == 'admin') {echo 'orange';}; ?>">
                        <?= htmlspecialchars($fetch_users['user_type']); ?>
                    </span></p>
                    <p>
                        Rating: 
                        <span class="stars">
                            <?php for ($i = 0; $i < $full_stars; $i++): ?>
                                <i class="fa fa-star filled"></i>
                            <?php endfor; ?>
                            <?php if ($half_star): ?>
                                <i class="fa fa-star-half-alt half"></i>
                            <?php endif; ?>
                            <?php for ($i = 0; $i < $empty_stars; $i++): ?>
                                <i class="fa fa-star fa-star-empty"></i>
                            <?php endfor; ?>
                        </span>
                        <span class="rating-value">(<?= htmlspecialchars(number_format($user_rating, 1)); ?>)</span>
                    </p>
                    <a href="admin_users.php?delete=<?= $fetch_users['id']; ?>" 
                       onclick="return confirm('Delete this user and their ratings?');" 
                       class="delete-btn">Delete</a>
                </div>
                <?php
                    }
                ?>
            </div>
        </section>
        <script src="js/script.js"></script>
    </body>
</html>
