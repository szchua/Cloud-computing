<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]--;
        if ($_SESSION['cart'][$product_id] <= 0) {
            unset($_SESSION['cart'][$product_id]);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('https://images.pexels.com/photos/1205651/pexels-photo-1205651.jpeg?cs=srgb&dl=pexels-emily-ranquist-493228-1205651.jpg&fm=jpg') no-repeat center center fixed;
            background-size: cover;
            background-color: #e6f0fa; /* Fallback light blue */
            color: #333;
        }
        .navbar { background-color: #003087; }
        .navbar-brand, .nav-link { color: white !important; }
        .table { background: rgba(255, 255, 255, 0.9); border-radius: 10px; }
        .btn-primary, .btn-success { background-color: #003087; border-color: #003087; }
        .btn-primary:hover, .btn-success:hover { background-color: #00205b; border-color: #00205b; }
        .btn-danger { background-color: #dc3545; border-color: #dc3545; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">Graduation Store</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Back to Store</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Order History</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="text-center mb-4" style="color: #fff; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Your Cart</h1>
        <?php
        if (empty($_SESSION['cart'])) {
            echo "<p class='text-center' style='background: rgba(255, 255, 255, 0.9); padding: 10px; border-radius: 5px;'>Your cart is empty. <a href='index.php'>Shop now!</a></p>";
        } else {
            echo "<table class='table table-striped'>";
            echo "<thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th>Action</th></tr></thead>";
            echo "<tbody>";
            $total = 0;
            foreach ($_SESSION['cart'] as $id => $quantity) {
                $result = $conn->query("SELECT * FROM products WHERE id = $id");
                $product = $result->fetch_assoc();
                $subtotal = $product['price'] * $quantity;
                $total += $subtotal;
                echo "<tr>";
                echo "<td>" . $product['name'] . "</td>";
                echo "<td>$" . number_format($product['price'], 2) . "</td>";
                echo "<td>" . $quantity . "</td>";
                echo "<td>$" . number_format($subtotal, 2) . "</td>";
                echo "<td><form method='post'><input type='hidden' name='product_id' value='" . $id . "'>";
                echo "<button type='submit' name='remove_from_cart' class='btn btn-danger btn-sm'>Remove</button></form></td>";
                echo "</tr>";
            }
            echo "<tr><td colspan='3'><strong>Total</strong></td><td><strong>$" . number_format($total, 2) . "</strong></td><td></td></tr>";
            echo "</tbody></table>";
            echo "<div class='text-center'><a href='checkout.php' class='btn btn-success'>Proceed to Checkout</a></div>";
        }
        $conn->close();
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>