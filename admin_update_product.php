<?php
@include 'config.php';
session_start();
$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:login.php');
}

if (isset($_POST['update_product'])) {
    $pid = $_POST['pid'];

    $name = $_POST['name'];
    $name = filter_var($name, FILTER_SANITIZE_STRING);

    $price = $_POST['price'];
    $price = filter_var($price, FILTER_SANITIZE_STRING);

    $catagory = $_POST['catagory'];
    $catagory = filter_var($catagory, FILTER_SANITIZE_STRING);

    $details = $_POST['details'];
    $details = filter_var($details, FILTER_SANITIZE_STRING);

    $stock = $_POST['stock'];  // Added stock field
    $stock = filter_var($stock, FILTER_SANITIZE_NUMBER_INT);

    $image = $_FILES['image']['name'];
    $image = filter_var($image, FILTER_SANITIZE_STRING);
    $image_size = $_FILES['image']['size'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = 'uploaded_img/' . $image;

    $old_image = $_POST['old_image'];

    // Update product details including stock
    $update_product = $conn->prepare("UPDATE `products` SET name = ?, catagory = ?, details = ?, price = ?, stock = ? WHERE id = ?");
    $update_product->execute([$name, $catagory, $details, $price, $stock, $pid]);

    $message[] = 'Successful product update!';

    // Image update if a new image is uploaded
    if (!empty($image)) {
        if ($image_size > 2000000) {
            $message[] = 'Image is too large!';
        } else {
            $update_image = $conn->prepare("UPDATE `products` SET image = ? WHERE id = ?");
            $update_image->execute([$image, $pid]);
            if ($update_image) {
                move_uploaded_file($image_tmp_name, $image_folder);
                unlink('uploaded_img/' . $old_image);
                $message[] = 'Successful image update!';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <section class="update-product">
        <h1 class="title">Update Product</h1>
        <?php
        $update_id = $_GET['update'];
        $select_products = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
        $select_products->execute([$update_id]);
        if ($select_products->rowCount() > 0) {
            while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
        ?>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="old_image" value="<?= $fetch_products['image']; ?>">
                    <input type="hidden" name="pid" value="<?= $fetch_products['id']; ?>">
                    <img src="uploaded_img/<?= $fetch_products['image']; ?>" alt="">
                    <input type="text" name="name" placeholder="Enter product name" required class="box" value="<?= $fetch_products['name']; ?>">
                    <input type="number" name="price" min="0" placeholder="Enter product price" required class="box" value="<?= $fetch_products['price']; ?>">
                    <select name="catagory" class="box" required>
                        <option selected><?= $fetch_products['catagory']; ?></option>
                        <option value="vegetables">Vegetables</option>
                        <option value="fruits">Fruits</option>
                        <option value="meat">Meat</option>
                        <option value="fish">Fish</option>
                    </select>
                    <textarea name="details" required placeholder="Enter product details" class="box" cols="30" rows="10"><?= $fetch_products['details']; ?></textarea>
                    <input type="number" name="stock" min="0" placeholder="Enter available stock" required class="box" value="<?= $fetch_products['stock']; ?>">
                    <input type="file" name="image" class="box" accept="image/jpg, image/jpeg, image/png">
                    <div class="flex-btn">
                        <input type="submit" value="Update Product" class="btn" name="update_product">
                        <a href="admin_products.php" class="option-btn">Go Back</a>
                    </div>
                </form>
        <?php
            }
        } else {
            echo '<p class="empty">No product found!</p>';
        }
        ?>
    </section>

    <script src="js/script.js"></script>
</body>

</html>
