<?php
require 'init.php';
require 'db.php';
init_db();

// Admin only
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$db = get_db();
$message = '';
$error = '';

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add new product
    if (isset($_POST['add_product'])) {
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $stock = intval($_POST['stock'] ?? 100);

        if ($name && $price > 0) {
            $stmt = $db->prepare('INSERT INTO products (name, price, description, image_url, stock) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $price, $description, $image_url, $stock]);
            $message = 'Product added.';
        } else {
            $error = 'Name and price are required.';
        }
    }

    // Edit product
    if (isset($_POST['edit_product'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $stock = intval($_POST['stock'] ?? 0);

        if ($id && $name && $price > 0) {
            $stmt = $db->prepare('UPDATE products SET name=?, price=?, description=?, image_url=?, stock=? WHERE id=?');
            $stmt->execute([$name, $price, $description, $image_url, $stock, $id]);
            $message = 'Product updated.';
        }
    }

    // Delete product
    if (isset($_POST['delete_product'])) {
        $id = intval($_POST['id']);
        $db->prepare('DELETE FROM cart WHERE product_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM purchases WHERE product_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        $message = 'Product deleted.';
    }
}

// Load products
$products = $db->query('SELECT * FROM products ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

// Load all orders
$orders = $db->query('SELECT pu.*, p.name AS product_name, u.username
    FROM purchases pu
    JOIN products p ON pu.product_id = p.id
    JOIN users u ON pu.username = u.username
    ORDER BY pu.purchased_at DESC
    LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);

// Total revenue
$totalSales = $db->query('SELECT SUM(total) FROM purchases')->fetchColumn() ?: 0;
$totalOrders = $db->query('SELECT COUNT(*) FROM purchases')->fetchColumn();
$totalUsers = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();

// Product being edited
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([intval($_GET['edit'])]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Urban Kicks</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Urban Kicks</h1>
</header>

<?php include 'nav.php'; ?>

<div id="content">
    <h2>Admin Dashboard</h2>

    <?php if ($message): ?>
        <p class="success"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <div class="admin-stats">
        <div class="stat-card">
            <strong><?php echo $totalOrders; ?></strong>
            <span>Total Orders</span>
        </div>
        <div class="stat-card">
            <strong>$<?php echo number_format($totalSales, 2); ?></strong>
            <span>Revenue</span>
        </div>
        <div class="stat-card">
            <strong><?php echo $totalUsers; ?></strong>
            <span>Users</span>
        </div>
        <div class="stat-card">
            <strong><?php echo count($products); ?></strong>
            <span>Products</span>
        </div>
    </div>

    <!-- Add / Edit Product -->
    <h3><?php echo $editing ? 'Edit Product' : 'Add New Product'; ?></h3>
    <form method="POST" class="admin-form">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?php echo $editing['id']; ?>">
        <?php endif; ?>
        <div class="form-row">
            <label>Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>" required>
        </div>
        <div class="form-row">
            <label>Price:</label>
            <input type="number" name="price" step="0.01" min="0.01" value="<?php echo htmlspecialchars($editing['price'] ?? ''); ?>" required>
        </div>
        <div class="form-row">
            <label>Description:</label>
            <input type="text" name="description" value="<?php echo htmlspecialchars($editing['description'] ?? ''); ?>">
        </div>
        <div class="form-row">
            <label>Image URL:</label>
            <input type="text" name="image_url" value="<?php echo htmlspecialchars($editing['image_url'] ?? ''); ?>">
        </div>
        <div class="form-row">
            <label>Stock:</label>
            <input type="number" name="stock" min="0" value="<?php echo $editing ? $editing['stock'] : 100; ?>">
        </div>
        <div class="form-row">
            <label></label>
            <?php if ($editing): ?>
                <input type="submit" name="edit_product" value="Save Changes" class="btn-primary">
                <a href="admin.php" class="btn-cancel">Cancel</a>
            <?php else: ?>
                <input type="submit" name="add_product" value="Add Product">
            <?php endif; ?>
        </div>
    </form>

    <!-- Product List -->
    <h3>Products</h3>
    <div class="admin-table-wrap">
    <table class="admin-table">
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
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?php echo $p['id']; ?></td>
                <td><img src="<?php echo htmlspecialchars($p['image_url']); ?>" alt="" class="admin-thumb"></td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td>$<?php echo number_format($p['price'], 2); ?></td>
                <td><?php echo $p['stock']; ?></td>
                <td class="admin-actions">
                    <a href="?edit=<?php echo $p['id']; ?>" class="btn-small">Edit</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete <?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>?')">
                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                        <input type="submit" name="delete_product" value="Delete" class="btn-small btn-danger">
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Orders -->
    <h3>Recent Orders</h3>
    <div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($orders)): ?>
            <tr><td colspan="6">No orders yet.</td></tr>
        <?php else: ?>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?php echo htmlspecialchars($o['purchased_at']); ?></td>
                <td><?php echo htmlspecialchars($o['username']); ?></td>
                <td><?php echo htmlspecialchars($o['product_name']); ?></td>
                <td><?php echo $o['quantity']; ?></td>
                <td>$<?php echo number_format($o['price'], 2); ?></td>
                <td>$<?php echo number_format($o['total'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

</body>
</html>
