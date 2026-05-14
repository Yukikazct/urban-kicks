<?php
require 'init.php';
require 'db.php';
init_db();
$db = get_db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - Urban Kicks</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Urban Kicks</h1>
</header>

<?php include 'nav.php'; ?>

<div id="content">
    <h2>Gallery</h2>

    <?php
    $stmt = $db->query('SELECT id, name, price, image_url FROM products ORDER BY id');
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product):
    ?>
    <div class="product">
        <a href="product.php?id=<?php echo $product['id']; ?>">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </a>
        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
        <p>$<?php echo number_format($product['price'], 2); ?></p>
        <form action="add_to_cart.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="submit" value="Add to Cart">
        </form>
    </div>
    <?php endforeach; ?>
</div>

</body>
</html>
