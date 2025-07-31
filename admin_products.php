<?php
    @include 'config.php';
    session_start();
    $admin_id = $_SESSION['admin_id'];

    if(!isset($admin_id)){
        header('location:login.php');
    };

    if(isset($_POST['add_product'])){
        $name = $_POST['name'];
        $name = filter_var($name, FILTER_SANITIZE_STRING);

        $price = $_POST['price'];
        $price = filter_var($price, FILTER_SANITIZE_STRING);

        $catagory = $_POST['catagory'];
        $catagory = filter_var($catagory, FILTER_SANITIZE_STRING);

        $details = $_POST['details'];
        $details = filter_var($details, FILTER_SANITIZE_STRING);

        $stock = $_POST['stock'];  // Getting stock value from the form
        $stock = filter_var($stock, FILTER_SANITIZE_NUMBER_INT);  // Sanitizing stock

        $image = $_FILES['image']['name'];
        $image = filter_var($image, FILTER_SANITIZE_STRING);
        $image_size = $_FILES['image']['size'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = 'uploaded_img/'.$image;

        $select_products = $conn->prepare("SELECT * FROM `products` WHERE name = ?");
        $select_products->execute([$name]);

        if($select_products->rowCount() > 0){
            $message[] = 'product name already exists!';
        }
        else{
            $insert_products = $conn->prepare("INSERT INTO `products`(name, catagory, details, price, stock, image) VALUES(?,?,?,?,?,?)");
            $insert_products->execute([$name, $catagory, $details, $price, $stock, $image]);

            if($insert_products){
                if($image_size > 2000000){
                    $message[] = 'image size is too large!';
                }
                else{
                    move_uploaded_file($image_tmp_name, $image_folder);
                    $message[] = 'new product added!';
                }
            }
        }
    }

    if(isset($_GET['delete'])){
        $delete_id = $_GET['delete'];
        $select_delete_image = $conn->prepare("SELECT image FROM `products` WHERE id = ?");
        $select_delete_image->execute([$delete_id]);
        $fetch_delete_image = $select_delete_image->fetch(PDO::FETCH_ASSOC);
        unlink('uploaded_img/'.$fetch_delete_image['image']);
        $delete_product = $conn->prepare("DELETE FROM `products` WHERE id = ?");
        $delete_product->execute([$delete_id]);
        $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE pid = ?");
        $delete_wishlist->execute([$delete_id]);
        $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE pid = ?");
        $delete_cart->execute([$delete_id]);
        header('location:admin_products.php');
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width", initial-scale=1.0>
        <title>Products</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        <link rel="stylesheet" href="css/admin_style.css">
    </head>
    <style>
        /* Styling for Available Stock Label */
        .show-products .box .stock {
            font-size: 16px;           /* Set the font size */
            color: #4CAF50;            /* Green color to indicate stock availability */
            font-weight: bold;         /* Make the text bold */
            padding: 10px 15px;        /* Add padding around the text */
            background-color: #f4f4f4; /* Light background to make the text pop */
            border-radius: 5px;        /* Rounded corners */
            display: flex;             /* Use flex to align the content */
            align-items: center;       /* Vertically align the content */
            justify-content: center;   /* Horizontally center the content */
            margin-top: 10px;          /* Space above the stock label */
            width: 100%;               /* Full width of the parent container */
            box-sizing: border-box;    /* Include padding and border in width calculation */
        }

        /* Optional: Add a color for low stock levels (if stock < 10) */
        .show-products .box .stock.low-stock {
            background-color: #FFEB3B; /* Yellow color for low stock */
            color: #D32F2F;            /* Red color for low stock text */
        }

        .show-products .box .stock.high-stock {
            background-color: #4CAF50; /* Green color for high stock */
            color: white;              /* White text for high stock */
        }

        /* Extra Styling for Product Boxes */
        .show-products .box {
            border: 1px solid #ddd;   /* Add light border for product box */
            padding: 20px;             /* Add padding around the content */
            background-color: #fff;    /* White background for each product */
            border-radius: 10px;       /* Rounded corners */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Light shadow for better depth */
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth hover effect */
            text-align: center;        /* Center-align the text inside the product box */
        }

        .show-products .box:hover {
            transform: translateY(-10px);  /* Slightly lift the box on hover */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); /* Stronger shadow */
        }

    </style>
    <body>
        <?php 
            include 'admin_header.php'; 
        ?>

        <section class="add-products">
            <h1 class="title">Add New Product</h1>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="flex">
                    <div class="inputBox">
                        <input type="text" name="name" class="box" required placeholder="Enter product name">
                        <select name="catagory" class="box" required>
                            <option value="" selected disabled>Choose Category</option>
                            <option value="vegetables">Vegetables</option>
                            <option value="fruits">Fruits</option>
                            <option value="meat">Meat</option>
                            <option value="fish">Fish</option>
                        </select>
                    </div>
                    <div class="inputBox">
                        <input type="number" min="0" name="price" class="box" required placeholder="Enter product price">
                        <input type="file" name="image" required class="box" accept="image/jpg, image/jpeg, image/png">
                    </div>
                </div>
                <textarea name="details" class="box" required placeholder="Enter product details" cols="30" rows="10"></textarea>
                <div class="inputBox">
                    <input type="number" min="0" name="stock" class="box" required placeholder="Available stock">
                </div>
                <input type="submit" class="btn" value="Add Product" name="add_product">
            </form>
        </section>

        <section class="show-products">
            <h1 class="title">Products Added</h1>
            <div class="box-container">
                <?php
                    $show_products = $conn->prepare("SELECT * FROM `products`");
                    $show_products->execute();
                    if($show_products->rowCount() > 0){
                        while($fetch_products = $show_products->fetch(PDO::FETCH_ASSOC)){
                ?>
                <div class="box">
                    <div class="price"><?= $fetch_products['price']; ?>/-</div>
                    <img src="uploaded_img/<?= $fetch_products['image']; ?>" alt="">
                    <div class="name"><?= $fetch_products['name']; ?></div>
                    <div class="cat"><?= $fetch_products['catagory']; ?></div>
                    <div class="details"><?= $fetch_products['details']; ?></div>
                    <div class="stock">Available Stock: <?= $fetch_products['stock']; ?></div> <!-- Showing stock -->
                    <div class="flex-btn">
                        <a href="admin_update_product.php?update=<?= $fetch_products['id']; ?>" class="option-btn">Update</a>
                        <a href="admin_products.php?delete=<?= $fetch_products['id']; ?>" class="delete-btn" onclick="return confirm('Delete this product?');">Delete</a>
                    </div>
                </div>
                <?php
                    }
                }
                else{
                    echo '<p class="empty">No products added yet!</p>';
                }
                ?>
            </div>
        </section>

        <script src="js/script.js"></script>
    </body>
</html>
