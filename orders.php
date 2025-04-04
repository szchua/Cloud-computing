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



$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
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
        .card { background: rgba(255, 255, 255, 0.9); border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .card-header { background-color: #003087; color: white; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">Graduation Store</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Back to Store</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php">Cart (<?php echo array_sum($_SESSION['cart'] ?? []); ?>)</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" onclick="confirmLogout(event)">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="text-center mb-4" style="color: #fff; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Order History</h1>
        <?php
        $result = $conn->query("SELECT * FROM orders WHERE user_id = $user_id ORDER BY order_date DESC");
        if ($result->num_rows == 0) {
            echo "<p class='text-center' style='background: rgba(255, 255, 255, 0.9); padding: 10px; border-radius: 5px;'>No orders found.</p>";
        } else {
            $order_count = $result->num_rows; // Total orders for this user
            $current_order_num = $order_count; // Start from the highest number (most recent order)
            while ($order = $result->fetch_assoc()) {
                echo "<div class='card mb-3'>";
                echo "<div class='card-header'>Order #$current_order_num (ID: " . $order['order_id'] . ") - " . $order['order_date'] . "</div>";
                echo "<div class='card-body'>";
                echo "<p><strong>Name:</strong> " . htmlspecialchars($order['customer_name']) . "</p>";
                echo "<p><strong>Email:</strong> " . htmlspecialchars($order['customer_email']) . "</p>";
                echo "<table class='table table-striped'>";
                echo "<thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead>";
                echo "<tbody>";
                $items_result = $conn->query("SELECT p.name, oi.quantity, oi.price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = " . $order['order_id']);
                while ($item = $items_result->fetch_assoc()) {
                    $subtotal = $item['quantity'] * $item['price'];
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($item['name']) . "</td>";
                    echo "<td>" . $item['quantity'] . "</td>";
                    echo "<td>$" . number_format($item['price'], 2) . "</td>";
                    echo "<td>$" . number_format($subtotal, 2) . "</td>";
                    echo "</tr>";
                }
                echo "<tr><td colspan='3'><strong>Total</strong></td><td><strong>$" . number_format($order['total'], 2) . "</strong></td></tr>";
                echo "</tbody></table>";
                echo "</div>";
                echo "</div>";
                $current_order_num--; // Decrease for the next (older) order
            }
        }
        $conn->close();
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmLogout(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to logout?")) {
            window.location.href = "logout.php";
        }
    }
</script>
</body>
</html>