<?php
require 'init.php';
require 'db.php';
init_db();

$db = get_db();
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare('SELECT id, name, price, description, image_url, stock FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Urban Kicks</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Urban Kicks</h1>
</header>

<?php include 'nav.php'; ?>

<div id="content">
    <p><a href="index.php">&larr; Back to Products</a></p>

    <div class="product-detail">
        <div class="product-detail-img">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="product-detail-info">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <p class="price-large">$<?php echo number_format($product['price'], 2); ?></p>
            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>

            <?php if ($product['stock'] <= 0): ?>
                <p class="out-of-stock">Out of Stock</p>
            <?php elseif ($product['stock'] < 10): ?>
                <p class="low-stock">Only <?php echo $product['stock']; ?> left in stock</p>
            <?php else: ?>
                <p class="in-stock">In Stock</p>
            <?php endif; ?>

            <?php if ($product['stock'] > 0): ?>
            <form action="add_to_cart.php" method="POST" class="detail-form">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <label for="quantity">Quantity:</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo min(10, $product['stock']); ?>">
                <input type="submit" value="Add to Cart" class="btn-primary">
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
