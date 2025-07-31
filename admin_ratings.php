<?php
    @include 'config.php';
    session_start();
    $admin_id = $_SESSION['admin_id'];

    if (!isset($admin_id)) {
        header('location:login.php');
        exit();
    }

    // Calculate average rating
    $avg_rating_query = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM `ratings`");
    $avg_rating_query->execute();
    $avg_rating_result = $avg_rating_query->fetch(PDO::FETCH_ASSOC);
    $average_rating = $avg_rating_result['avg_rating'] ?? 0;

    // Calculate star ratings (full, half, empty)
    $full_stars = floor($average_rating);
    $half_star = ($average_rating - $full_stars >= 0.5) ? 1 : 0;
    $empty_stars = 5 - $full_stars - $half_star;
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Average User Rating</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        <link rel="stylesheet" href="css/admin_style.css">
        <style>
            /* Ensure everything else stays normal */
            body {
                background-color: #f4f4f4; /* Light background */
                font-family: Arial, sans-serif;
            }

            /* Centering only the average rating box */
            .rating-container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh; /* Full viewport height */
                margin-top: 50px;
                position: relative; /* Relative positioning to move it */
                top: -192px; /* Move it 2 inches (192px) up from the center */
            }

            .rating-box {
                background-color: #fff;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
                text-align: center;
                width: 100%;
                max-width: 500px; /* Set a max-width for the box */
                transition: all 0.3s ease-in-out;
            }

            .rating-box:hover {
                box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
                transform: translateY(-5px);
            }

            .stars {
                color: #f39c12; /* Golden color for the stars */
                font-size: 3rem; /* Make stars larger */
                margin-bottom: 10px;
            }

            .stars .fa-star,
            .stars .fa-star-half-alt {
                opacity: 1;
            }

            .stars .fa-star-empty {
                opacity: 0.3; /* Dim empty stars */
            }

            .rating-value {
                font-size: 1.5rem;
                color: #333;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <?php include 'admin_header.php'; ?> <!-- Keep header outside of the centered box -->

        <!-- Centered Average Rating Box -->
        <div class="rating-container">
            <div class="rating-box">
                <h1 class="title">Average User Rating</h1>
                <div class="stars">
                    <?php for ($i = 0; $i < $full_stars; $i++): ?>
                        <i class="fa fa-star"></i>
                    <?php endfor; ?>
                    <?php if ($half_star): ?>
                        <i class="fa fa-star-half-alt"></i>
                    <?php endif; ?>
                    <?php for ($i = 0; $i < $empty_stars; $i++): ?>
                        <i class="fa fa-star"></i>
                    <?php endfor; ?>
                </div>
                <p class="rating-value">(<?= htmlspecialchars(number_format($average_rating, 1)); ?>)</p>
            </div>
        </div>

        <script src="js/script.js"></script>
    </body>
</html>
