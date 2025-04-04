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

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Initialize form variables
$customer_name = $_POST['customer_name'] ?? '';
$customer_email = $_POST['customer_email'] ?? '';
$card_number = $_POST['card_number'] ?? '';
$card_expiry = $_POST['card_expiry'] ?? '';
$card_cvc = $_POST['card_cvc'] ?? '';

// Check if payment is confirmed and form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_transaction'])) {
    $customer_name = $_POST['customer_name'];
    $customer_email = $_POST['customer_email'];
    $card_number = $_POST['card_number'];
    $card_expiry = $_POST['card_expiry'];
    $card_cvc = $_POST['card_cvc'];
    $user_id = $_SESSION['user_id'];
    $total = 0;

    $stock_ok = true;
    foreach ($_SESSION['cart'] as $id => $quantity) {
        $result = $conn->query("SELECT price, stock FROM products WHERE id = $id");
        $product = $result->fetch_assoc();
        if ($product['stock'] < $quantity) {
            $stock_ok = false;
            break;
        }
        $total += $product['price'] * $quantity;
    }

    if ($stock_ok) {
        if (strlen($card_number) == 16 && preg_match("/^\d{2}\/\d{2}$/", $card_expiry) && strlen($card_cvc) == 3) {
            $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_email, total, user_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssdi", $customer_name, $customer_email, $total, $user_id);
            $stmt->execute();
            $order_id = $conn->insert_id;

            $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            foreach ($_SESSION['cart'] as $id => $quantity) {
                $result = $conn->query("SELECT price FROM products WHERE id = $id");
                $product = $result->fetch_assoc();
                $price = $product['price'];
                $stmt_items->bind_param("iiid", $order_id, $id, $quantity, $price);
                $stmt_items->execute();
                $stmt_stock->bind_param("ii", $quantity, $id);
                $stmt_stock->execute();
            }

            unset($_SESSION['cart']);
            $stmt->close();
            $stmt_items->close();
            $stmt_stock->close();
            $conn->close();
            header("Location: index.php?transaction=success");
            exit();
        } else {
            $error = "Invalid credit card details. Please check the card number (16 digits), expiry date (MM/YY), and CVC (3 digits).";
        }
    } else {
        $error = "Insufficient stock for one or more items.";
    }
}

// Check if payment confirmation is submitted
$show_payment_form = isset($_POST['confirm_payment']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
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
        .checkout-form, .table { background: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .btn-primary { background-color: #003087; border-color: #003087; }
        .btn-primary:hover { background-color: #00205b; border-color: #00205b; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">Graduation Store</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="cart.php">Back to Cart</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Order History</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="confirmLogout(event)">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="text-center mb-4" style="color: #fff; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Checkout</h1>
        <?php if (isset($error)) echo "<div class='alert alert-danger text-center'>$error</div>"; ?>

        <!-- Cart Summary -->
        <div class="table mb-4">
            <h3 class="text-center mb-3">Order Summary</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    foreach ($_SESSION['cart'] as $id => $quantity) {
                        $stmt = $conn->prepare("SELECT name, price FROM products WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $product = $result->fetch_assoc();
                        $subtotal = $product['price'] * $quantity;
                        $total += $subtotal;
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                        echo "<td>RM " . number_format($product['price'], 2) . "</td>";
                        echo "<td>" . $quantity . "</td>";
                        echo "<td>RM " . number_format($subtotal, 2) . "</td>";
                        echo "</tr>";
                        $stmt->close();
                    }
                    echo "<tr><td colspan='3'><strong>Total</strong></td><td><strong>RM " . number_format($total, 2) . "</strong></td></tr>";
                    ?>
                </tbody>
            </table>

            <?php if (!$show_payment_form): ?>
                <form method="post" class="text-center">
                    <button type="submit" name="confirm_payment" class="btn btn-primary">Confirm Payment</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Payment Form (shown only after confirmation) -->
        <?php if ($show_payment_form): ?>
            <div class="checkout-form w-50 mx-auto">
                <h3 class="text-center mb-3">Payment Details</h3>
                <form method="post">
                    <div class="mb-3">
                        <label for="customer_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="customer_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="customer_email" name="customer_email" value="<?php echo htmlspecialchars($customer_email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="card_number" class="form-label">Credit Card Number (16 digits)</label>
                        <input type="text" class="form-control" id="card_number" name="card_number" maxlength="16" pattern="\d{16}" value="<?php echo htmlspecialchars($card_number); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="card_expiry" class="form-label">Expiry Date (MM/YY)</label>
                        <input type="text" class="form-control" id="card_expiry" name="card_expiry" maxlength="5" pattern="\d{2}/\d{2}" placeholder="MM/YY" value="<?php echo htmlspecialchars($card_expiry); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="card_cvc" class="form-label">CVC (3 digits)</label>
                        <input type="text" class="form-control" id="card_cvc" name="card_cvc" maxlength="3" pattern="\d{3}" value="<?php echo htmlspecialchars($card_cvc); ?>" required>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="complete_transaction" class="btn btn-primary">Complete Transaction</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
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