<?php
session_start();
include 'db_config.php'; // Database connection

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect if user is an admin
if ($_SESSION['is_admin']) {
    header("Location: admin_products.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Get the user ID

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    error_log("POST Data: " . print_r($_POST, true), 3, 'errors.log'); // Debug POST data
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    // Check product stock
    $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error, 3, 'errors.log');
        echo "<div class='alert alert-danger'>Database error: Unable to check product stock.</div>";
        exit();
    }
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if (!$product) {
        echo "<div class='alert alert-danger'>Product not found.</div>";
        exit();
    }

    if ($quantity > 0 && $quantity <= $product['stock']) {
        // Check if the product is already in the cart
        $stmt = $conn->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error, 3, 'errors.log');
            echo "<div class='alert alert-danger'>Database error: Unable to check cart.</div>";
            exit();
        }
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update quantity if product exists in cart
            $row = $result->fetch_assoc();
            $new_quantity = $row['quantity'] + $quantity;
            if ($new_quantity <= $product['stock']) {
                $stmt_update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
                if (!$stmt_update) {
                    error_log("Prepare failed: " . $conn->error, 3, 'errors.log');
                    echo "<div class='alert alert-danger'>Database error: Unable to update cart.</div>";
                    exit();
                }
                $stmt_update->bind_param("ii", $new_quantity, $row['cart_item_id']);
                if ($stmt_update->execute()) {
                    $_SESSION['cart_message'] = "<div class='alert alert-success'>Cart updated successfully.</div>";
                } else {
                    error_log("Update failed: " . $conn->error, 3, 'errors.log');
                    $_SESSION['cart_message'] = "<div class='alert alert-danger'>Error updating cart: " . $conn->error . "</div>";
                }
                $stmt_update->close();
            } else {
                $_SESSION['cart_message'] = "<div class='alert alert-danger'>Not enough stock available for the requested quantity.</div>";
            }
        } else {
            // Insert new cart item
            $stmt_insert = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
            if (!$stmt_insert) {
                error_log("Prepare failed: " . $conn->error, 3, 'errors.log');
                $_SESSION['cart_message'] = "<div class='alert alert-danger'>Database error: Unable to prepare insert.</div>";
                exit();
            }
            $stmt_insert->bind_param("iii", $user_id, $product_id, $quantity);
            if ($stmt_insert->execute()) {
                $_SESSION['cart_message'] = "<div class='alert alert-success'>Item added to cart.</div>";
            } else {
                error_log("Insert failed: " . $conn->error, 3, 'errors.log');
                $_SESSION['cart_message'] = "<div class='alert alert-danger'>Error adding item to cart: " . $conn->error . "</div>";
            }
            $stmt_insert->close();
        }
        $stmt->close();
    } else {
        $_SESSION['cart_message'] = "<div class='alert alert-danger'>Invalid quantity or insufficient stock.</div>";
    }

    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch cart item count for the navbar
$stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_count = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();
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
            height: 110px; 
            object-fit: contain; 
            border-radius: 10px 10px 0 0; 
            background-color: #fff; 
        }
    </style>
</head>
<body>
<!-- Cart Status and Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="#">Graduation Store</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="cart.php">Cart (<?php echo $cart_count; ?>)</a></li>
                <li class="nav-item"><a class="nav-link" href="orders.php">Order History</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Display Messages -->
<div class="container mt-4">
    <?php
    if (isset($_SESSION['cart_message'])) {
        echo $_SESSION['cart_message'];
        unset($_SESSION['cart_message']);
    }
    ?>

    <!-- Product Display -->
    <h1 class="text-center mb-4" style="color: #fff; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Welcome to TARUMT Graduation Store</h1>
    <div class="row">
        <?php
        $result = $conn->query("SELECT * FROM products");
        while ($row = $result->fetch_assoc()) {
            $disabled = $row['stock'] <= 0 ? 'disabled' : '';
            echo "<div class='col-md-4 mb-4'>";
            echo "<div class='card product-card'>";
            echo "<img src='" . htmlspecialchars($row['image']) . "' class='product-image' alt='" . htmlspecialchars($row['name']) . "' onerror=\"this.src='https://via.placeholder.com/150?text=Image+Not+Found';\">";
            echo "<div class='card-body'>";
            echo "<h5 class='card-title'>" . htmlspecialchars($row['name']) . "</h5>";
            echo "<p class='card-text'>Price: RM " . number_format($row['price'], 2) . "</p>";
            echo "<p class='card-text'>Stock: " . $row['stock'] . "</p>";
            echo "<button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#quantityModal" . $row['id'] . "' $disabled>Add to Cart</button>";
            echo "</div>";
            echo "</div>";
            echo "</div>";

            echo "<div class='modal fade' id='quantityModal" . $row['id'] . "' tabindex='-1' aria-labelledby='quantityModalLabel' aria-hidden='true'>";
            echo "<div class='modal-dialog'>";
            echo "<div class='modal-content'>";
            echo "<div class='modal-header'>";
            echo "<h5 class='modal-title' id='quantityModalLabel'>Select Quantity for " . htmlspecialchars($row['name']) . "</h5>";
            echo "<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>";
            echo "</div>";
            echo "<div class='modal-body'>";
            echo "<form method='post'>";
            echo "<input type='hidden' name='product_id' value='" . $row['id'] . "'>";
            echo "<div class='input-group w-50 mx-auto'>";
            echo "<input type='number' name='quantity' class='form-control quantity-input' value='1' min='1' max='" . $row['stock'] . "'>";
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
document.querySelectorAll('.quantity-input').forEach(input => {
    input.previousElementSibling.addEventListener('click', () => input.stepDown());
    input.nextElementSibling.addEventListener('click', () => input.stepUp());
});
</script>
</body>
</html>