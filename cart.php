<?php
// Enable error reporting to catch any PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: text/html; charset=UTF-8'); // Ensure UTF-8 encoding
include 'db_config.php';

// Ensure database connection uses UTF-8
$conn->set_charset("utf8mb4");

// Redirect if not logged in or if user is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['is_admin']) {
    header("Location: admin_products.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = ''; // To store success/error messages

// Handle cart operations (remove, update quantity)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['remove_from_cart'])) {
        $cart_item_id = (int)$_POST['cart_item_id'];
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND cart_item_id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error, 3, 'errors.log');
            $message = "<div class='alert alert-danger text-center'>Error preparing delete query.</div>";
        } else {
            $stmt->bind_param("ii", $user_id, $cart_item_id);
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success text-center'>Item removed from cart.</div>";
            } else {
                error_log("Delete failed: " . $stmt->error, 3, 'errors.log');
                $message = "<div class='alert alert-danger text-center'>Error removing item from cart.</div>";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['update_quantity'])) {
        $cart_item_id = (int)$_POST['cart_item_id'];
        $quantity = max(1, (int)$_POST['quantity']); // Ensure quantity is at least 1

        // Check stock before updating
        $stmt_stock = $conn->prepare("SELECT p.stock 
                                      FROM cart_items ci 
                                      JOIN products p ON ci.product_id = p.id 
                                      WHERE ci.cart_item_id = ? AND ci.user_id = ?");
        if (!$stmt_stock) {
            error_log("Prepare failed: " . $conn->error, 3, 'errors.log');
            $message = "<div class='alert alert-danger text-center'>Error checking stock.</div>";
        } else {
            $stmt_stock->bind_param("ii", $cart_item_id, $user_id);
            $stmt_stock->execute();
            $result_stock = $stmt_stock->get_result();
            $stock = $result_stock->fetch_assoc()['stock'] ?? 0;
            $stmt_stock->close();

            if ($quantity <= $stock) {
                $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND cart_item_id = ?");
                if (!$stmt) {
                    error_log("Prepare failed: " . $conn->error, 3, 'errors.log');
                    $message = "<div class='alert alert-danger text-center'>Error preparing update query.</div>";
                } else {
                    $stmt->bind_param("iii", $quantity, $user_id, $cart_item_id);
                    if ($stmt->execute()) {
                        $message = "<div class='alert alert-success text-center'>Quantity updated successfully.</div>";
                    } else {
                        error_log("Update failed: " . $stmt->error, 3, 'errors.log');
                        $message = "<div class='alert alert-danger text-center'>Error updating quantity.</div>";
                    }
                    $stmt->close();
                }
            } else {
                $message = "<div class='alert alert-danger text-center'>Quantity exceeds available stock ($stock).</div>";
            }
        }
    }
}

// Fetch cart items from the database
$stmt = $conn->prepare("SELECT ci.cart_item_id, ci.product_id, ci.quantity, p.name, p.price, p.stock 
                        FROM cart_items ci 
                        JOIN products p ON ci.product_id = p.id 
                        WHERE ci.user_id = ?");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error, 3, 'errors.log');
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error, 3, 'errors.log');
    die("Execute failed: " . $stmt->error);
}
$cart_items = $stmt->get_result();
if (!$cart_items) {
    error_log("Get result failed: " . $stmt->error, 3, 'errors.log');
    die("Get result failed: " . $stmt->error);
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
            background-color: #e6f0fa;
            color: #333;
        }
        .navbar { background-color: #003087; }
        .navbar-brand, .nav-link { color: white !important; }
        .table { background: rgba(255, 255, 255, 0.9); border-radius: 10px; }
        .btn-primary, .btn-success { background-color: #003087; border-color: #003087; }
        .btn-primary:hover, .btn-success:hover { background-color: #00205b; border-color: #00205b; }
        .btn-danger { background-color: #dc3545; border-color: #dc3545; }
        .form-control.d-inline-block { width: 80px; }
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
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="confirmLogout(event)">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="text-center mb-4" style="color: #fff; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Your Cart</h1>
        <?php
        if (!empty($message)) {
            echo $message;
        }
        if ($cart_items->num_rows == 0) {
            echo "<p class='text-center' style='background: rgba(255, 255, 255, 0.9); padding: 10px; border-radius: 5px;'>Your cart is empty. <a href='index.php'>Shop now!</a></p>";
        } else {
            echo "<table class='table table-striped'>";
            echo "<thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th>Action</th></tr></thead>";
            echo "<tbody>";
            $total = 0;

            while ($item = $cart_items->fetch_assoc()) {
                $subtotal = $item['price'] * $item['quantity'];
                $total += $subtotal;
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item['name']) . "</td>";
                echo "<td>RM " . number_format($item['price'], 2) . "</td>";
                echo "<td>
                    <form method='post' class='d-inline'>
                        <input type='hidden' name='cart_item_id' value='" . $item['cart_item_id'] . "'>
                        <input type='number' name='quantity' class='form-control d-inline-block' value='" . $item['quantity'] . "' min='1' max='" . $item['stock'] . "'>
                        <button type='submit' name='update_quantity' class='btn btn-primary btn-sm mt-1'>Update</button>
                    </form>
                </td>";
                echo "<td>RM " . number_format($subtotal, 2) . "</td>";
                echo "<td>
                    <form method='post'>
                        <input type='hidden' name='cart_item_id' value='" . $item['cart_item_id'] . "'>
                        <button type='submit' name='remove_from_cart' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to remove this item?\");'>Remove</button>
                    </form>
                </td>";
                echo "</tr>";
            }

            echo "<tr><td colspan='3'><strong>Total</strong></td><td><strong>RM " . number_format($total, 2) . "</strong></td><td></td></tr>";
            echo "</tbody></table>";
            echo "<div class='text-center'><a href='checkout.php' class='btn btn-success'>Proceed to Checkout</a></div>";
        }
        $stmt->close();
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