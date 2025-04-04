<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['create'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $image = $_POST['image'];

    $stmt = $conn->prepare("INSERT INTO products (name, price, image, stock) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdsi", $name, $price, $image, $stock);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $image = $_POST['image'];

    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, image = ?, stock = ? WHERE id = ?");
    $stmt->bind_param("sdsii", $name, $price, $image, $stock, $id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Handle search
$search_query = isset($_POST['search']) ? $_POST['search'] : '';
$sql = "SELECT * FROM products" . ($search_query ? " WHERE name LIKE ?" : "");
$stmt = $conn->prepare($sql);
if ($search_query) {
    $search_term = "%" . $search_query . "%";
    $stmt->bind_param("s", $search_term);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Products</title>
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
        .btn-primary { background-color: #003087; border-color: #003087; }
        .btn-primary:hover { background-color: #00205b; border-color: #00205b; }
        .btn-danger { background-color: #dc3545; border-color: #dc3545; }
        .form-container { background: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .search-container { background: rgba(255, 255, 255, 0.9); padding: 15px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); margin-bottom: 20px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Panel</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="confirmLogout(event)">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="text-center mb-4" style="color: #fff; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Manage Products</h1>

        <div class="form-container mb-4">
            <h3>Add New Product</h3>
            <form method="post">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Price (RM)</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                </div>
                <div class="mb-3">
                    <label for="stock" class="form-label">Stock</label>
                    <input type="number" class="form-control" id="stock" name="stock" required>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Image URL</label>
                    <input type="url" class="form-control" id="image" name="image" placeholder="https://example.com/image.jpg" required>
                </div>
                <button type="submit" name="create" class="btn btn-primary">Add Product</button>
            </form>
        </div>

        <!-- Search Form -->
        <div class="search-container mb-4">
            <h3>Search Products</h3>
            <form method="post" class="d-flex">
                <input type="text" class="form-control me-2" name="search" placeholder="Enter product name..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td><img src='" . $row['image'] . "' alt='" . $row['name'] . "' style='width: 50px; height: 50px; object-fit: cover;'></td>";
                    echo "<td>" . $row['name'] . "</td>";
                    echo "<td>RM " . number_format($row['price'], 2) . "</td>";
                    echo "<td>" . $row['stock'] . "</td>";
                    echo "<td>";
                    echo "<button class='btn btn-primary btn-sm' data-bs-toggle='modal' data-bs-target='#editModal" . $row['id'] . "'>Edit</button> ";
                    echo "<a href='admin_products.php?delete=" . $row['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure?\");'>Delete</a>";
                    echo "</td>";
                    echo "</tr>";

                    echo "<div class='modal fade' id='editModal" . $row['id'] . "' tabindex='-1' aria-labelledby='editModalLabel" . $row['id'] . "' aria-hidden='true'>";
                    echo "<div class='modal-dialog'>";
                    echo "<div class='modal-content'>";
                    echo "<div class='modal-header'>";
                    echo "<h5 class='modal-title' id='editModalLabel" . $row['id'] . "'>Edit " . $row['name'] . "</h5>";
                    echo "<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>";
                    echo "</div>";
                    echo "<div class='modal-body'>";
                    echo "<form method='post'>";
                    echo "<input type='hidden' name='id' value='" . $row['id'] . "'>";
                    echo "<div class='mb-3'><label class='form-label'>Name</label><input type='text' class='form-control' name='name' value='" . $row['name'] . "' required></div>";
                    echo "<div class='mb-3'><label class='form-label'>Price (RM)</label><input type='number' step='0.01' class='form-control' name='price' value='" . $row['price'] . "' required></div>";
                    echo "<div class='mb-3'><label class='form-label'>Stock</label><input type='number' class='form-control' name='stock' value='" . $row['stock'] . "' required></div>";
                    echo "<div class='mb-3'><label class='form-label'>Image URL</label><input type='url' class='form-control' name='image' value='" . $row['image'] . "' required></div>";
                    echo "</div>";
                    echo "<div class='modal-footer'>";
                    echo "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>";
                    echo "<button type='submit' name='update' class='btn btn-primary'>Save Changes</button>";
                    echo "</form>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                }
                $stmt->close();
                $conn->close();
                ?>
            </tbody>
        </table>
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