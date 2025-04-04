<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['is_admin']) {
    header("Location: admin_products.php");
    exit();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $result = $conn->query("SELECT stock FROM products WHERE id = $product_id");
    $product = $result->fetch_assoc();
    if ($quantity > 0 && $quantity <= $product['stock']) {
        if (!isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = $quantity;
        } else if ($_SESSION['cart'][$product_id] + $quantity <= $product['stock']) {
            $_SESSION['cart'][$product_id] += $quantity;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TARUMT Graduation Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('https://images.pexels.com/photos/1205651/pexels-photo-1205651.jpeg?cs=srgb&dl=pexels-emily-ranquist-493228-1205651.jpg&fm=jpg') no-repeat center center fixed;
            background-size: cover;
            background-color: #e6f0fa;
            color: #333;
        }
        .navbar { background-color: #003087; }
        .navbar-brand, .nav-link { color: white !important; }
        .product-card { background: rgba(255, 255, 255, 0.9); border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .product-card:hover { transform: scale(1.05); transition: transform 0.2s; }
        .btn-primary { background-color: #003087; border-color: #003087; }
        .btn-primary:hover { background-color: #00205b; border-color: #00205b; }
        .modal-content { background: rgba(255, 255, 255, 0.95); }
        .quantity-input { width: 50px; display: inline-block; }
        .product-image { 
            width: 100%; 
            height: 75px; 
            object-fit: contain; 
            border-radius: 10px 10px 0 0; 
            background-color: #fff; 
        }
    </style>
</head>
<body>
    <?php
    if (isset($_GET['transaction']) && $_GET['transaction'] == 'success') {
        echo "<div class='alert alert-success text-center'>Transaction completed successfully! Thank you for your purchase.</div>";
    }
    ?>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">Graduation Store</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="cart.php">Cart (<?php echo array_sum($_SESSION['cart']); ?>)</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Order History</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="confirmLogout(event)">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="text-center mb-4" style="color: #fff; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Welcome to TARUMT Graduation Store</h1>
        <div class="row">
            <?php
            $result = $conn->query("SELECT * FROM products");
            while ($row = $result->fetch_assoc()) {
                $disabled = $row['stock'] <= 0 ? 'disabled' : '';
                echo "<div class='col-md-4 mb-4'>";
                echo "<div class='card product-card'>";
                echo "<img src='" . $row['image'] . "' class='product-image' alt='" . $row['name'] . "' onerror=\"this.src='https://via.placeholder.com/150?text=Image+Not+Found';\">";
                echo "<div class='card-body'>";
                echo "<h5 class='card-title'>" . $row['name'] . "</h5>";
                echo "<p class='card-text'>Price: $" . number_format($row['price'], 2) . "</p>";
                echo "<p class='card-text'>Stock: " . $row['stock'] . "</p>";
                echo "<button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#quantityModal" . $row['id'] . "' $disabled>Add to Cart</button>";
                echo "</div>";
                echo "</div>";
                echo "</div>";

                echo "<div class='modal fade' id='quantityModal" . $row['id'] . "' tabindex='-1' aria-labelledby='quantityModalLabel' aria-hidden='true'>";
                echo "<div class='modal-dialog'>";
                echo "<div class='modal-content'>";
                echo "<div class='modal-header'>";
                echo "<h5 class='modal-title' id='quantityModalLabel'>Select Quantity for " . $row['name'] . "</h5>";
                echo "<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>";
                echo "</div>";
                echo "<div class='modal-body'>";
                echo "<form method='post'>";
                echo "<input type='hidden' name='product_id' value='" . $row['id'] . "'>";
                echo "<div class='input-group w-50 mx-auto'>";
                echo "<button type='button' class='btn btn-outline-secondary' onclick='this.nextElementSibling.stepDown()'>-</button>";
                echo "<input type='number' name='quantity' class='form-control quantity-input' value='1' min='1' max='" . $row['stock'] . "' readonly>";
                echo "<button type='button' class='btn btn-outline-secondary' onclick='this.previousElementSibling.stepUp()'>+</button>";
                echo "</div>";
                echo "</div>";
                echo "<div class='modal-footer'>";
                echo "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>";
                echo "<button type='submit' name='add_to_cart' class='btn btn-primary'>Add to Cart</button>";
                echo "</form>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            $conn->close();
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmLogout(event) {
            event.preventDefault(); // Prevent the default link behavior
            if (confirm("Are you sure you want to logout?")) {
                window.location.href = "logout.php"; // Redirect to logout.php if confirmed
            }
        }
    </script>
</body>
</html>