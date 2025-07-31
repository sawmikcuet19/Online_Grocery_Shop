<?php
@include 'config.php'; // Include database connection
session_start();

$user_id = $_SESSION['user_id']; // Fetch logged-in user ID

if (!isset($user_id)) {
    header('location:login.php');
    exit();
}

// Initialize notification message array
$message = [];

// Fetch existing ratings
$select_ratings = $conn->prepare("SELECT * FROM `ratings` WHERE user_id = ?");
$select_ratings->execute([$user_id]);
$fetch_rating = $select_ratings->fetch(PDO::FETCH_ASSOC);
$current_rating = $fetch_rating['rating'] ?? 0; // Default rating is 0

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_rating'])) {
        // Handle delete action
        $delete_rating = $conn->prepare("DELETE FROM `ratings` WHERE user_id = ?");
        $delete_rating->execute([$user_id]);
        $current_rating = 0;
        $message[] = 'Your rating has been deleted!';
    } elseif (isset($_POST['rating']) && is_numeric($_POST['rating'])) {
        // Handle rating submission
        $new_rating = floatval($_POST['rating']);
        if ($fetch_rating) {
            $update_rating = $conn->prepare("UPDATE `ratings` SET rating = ? WHERE user_id = ?");
            $update_rating->execute([$new_rating, $user_id]);
            $message[] = 'Your rating has been updated!';
        } else {
            $insert_rating = $conn->prepare("INSERT INTO `ratings` (user_id, rating) VALUES (?, ?)");
            $insert_rating->execute([$user_id, $new_rating]);
            $message[] = 'Your rating has been submitted!';
        }
        $current_rating = $new_rating;
    }
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rating</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .rating-container {
            text-align: center;
            margin: 60px auto;
            max-width: 400px;
            padding: 40px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #f9f9f9;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .rating-container h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .stars {
            position: relative;
            display: inline-block;
            font-size: 5rem;
        }

        .stars .hover-layer,
        .stars .click-layer {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, #f39c12 0%, #f39c12 100%);
            overflow: hidden;
            pointer-events: none;
            transition: width 0.3s ease;
            z-index: 1;
        }

        .stars .background-layer {
            color: #ccc;
        }

        .stars span {
            cursor: pointer;
            position: relative;
            z-index: 2;
            display: inline-block;
            width: 20%;
        }

        .stars span:hover,
        .stars span:hover ~ span {
            color: #f39c12;
        }

        .numeric-rating {
            margin-top: 10px;
            font-size: 1.2rem;
            color: #555;
        }

        button.submit-rating {
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #28a745;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button.submit-rating:hover {
            background-color: #218838;
        }

        button.delete-rating {
            margin-top: 10px;
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #e74c3c;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button.delete-rating:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    
    <section class="rating-section">
        <h1 class="title">Rate Our Service</h1>

        <!-- Display Messages -->
        <?php if (!empty($message) && is_array($message)): ?>
            <?php foreach ($message as $msg): ?>
                <div class="message"><?= htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="rating-container">
            <form action="" method="POST">
                <div class="stars" id="rating-stars" data-current-rating="<?= $current_rating; ?>">
                <div class="hover-layer"></div>
                    <div class="click-layer" style="width: <?php echo ($current_rating / 5) * 100; ?>%;"></div>
                    <div class="background-layer">
                        <span data-value="5">★</span>
                        <span data-value="4">★</span>
                        <span data-value="3">★</span>
                        <span data-value="2">★</span>
                        <span data-value="1">★</span>
                    </div>
                </div>
                <input type="hidden" name="rating" id="rating-input" value="<?= $current_rating; ?>">
                <div class="numeric-rating" id="numeric-rating">
                    Current Rating: <?= $current_rating ? number_format($current_rating, 1) : 'None'; ?>
                </div>
                <button type="submit" class="submit-rating">Submit</button>
                <button type="submit" name="delete_rating" class="delete-rating" <?= $current_rating ? '' : 'disabled'; ?>>Delete</button>
            </form>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script>
        const starsContainer = document.getElementById('rating-stars');
        const hoverLayer = starsContainer.querySelector('.hover-layer');
        const clickLayer = starsContainer.querySelector('.click-layer');
        const ratingInput = document.getElementById('rating-input');
        const ratingDisplay = document.getElementById('numeric-rating');

        let currentRating = <?php echo $current_rating; ?>; // Use the rating from PHP
        const starWidth = 20; // Percentage width of one star

        // Handle hover event to show decimal rating dynamically
        starsContainer.addEventListener('mousemove', (e) => {
            const rect = starsContainer.getBoundingClientRect();
            const offsetX = e.clientX - rect.left;
            const hoverRating = Math.min(5, Math.max(0, (offsetX / rect.width) * 5)).toFixed(1);
            hoverLayer.style.width = `${(hoverRating / 5) * 100}%`;
            ratingDisplay.textContent = `Hover to see rating: ${hoverRating}`;
        });

        // Handle click event to select a rating
        starsContainer.addEventListener('click', (e) => {
            const rect = starsContainer.getBoundingClientRect();
            const offsetX = e.clientX - rect.left;
            currentRating = Math.min(5, Math.max(0, (offsetX / rect.width) * 5)).toFixed(1);
            clickLayer.style.width = `${(currentRating / 5) * 100}%`;
            ratingInput.value = currentRating; // Save the rating to hidden input
            ratingDisplay.textContent = `Selected Rating: ${currentRating}`;
        });

        // Reset hover effect when mouse leaves
        starsContainer.addEventListener('mouseleave', () => {
            hoverLayer.style.width = '0';
            ratingDisplay.textContent = `Selected Rating: ${currentRating || 'None'}`;
        });
    </script>
    <script src="js/script.js"></script>
</body>
</html>
