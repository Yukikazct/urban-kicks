<?php
require 'init.php';
require 'db.php';
init_db();
$db = get_db();

$search = trim($_GET['search'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urban Kicks</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Urban Kicks</h1>
    <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEiNXKe3GTbcjdqvX9YlFOBE0tAB-l5MnoT_D7zyXqOmuzm3fX57Url44VlGJIUXr3YbEeWBBh95DBAOdbrwTdkzPZfqnA8M_ueN7uazMv4SCUhcf8entgJrIv_u-hsPGG_ZuVd8KKbI5Zr0oli-40C0BQm5sj7uN6kE_d7I-Cg-YWOO9Hrc0t6i7ktb/s1920/shoe%20banner%20by%20lincungstock.jpg" alt="Sneaker banner" class="banner">
</header>

<?php include 'nav.php'; ?>

<div id="content">
    <h2>Featured Products</h2>

    <form action="index.php" method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search sneakers..." value="<?php echo htmlspecialchars($search); ?>">
        <input type="submit" value="Search">
        <?php if ($search): ?>
            <a href="index.php" class="clear-search">Clear</a>
        <?php endif; ?>
    </form>

    <?php
    if ($search) {
        $stmt = $db->prepare('SELECT id, name, price, description, image_url, stock FROM products WHERE name LIKE ? ORDER BY id');
        $stmt->execute(['%' . $search . '%']);
    } else {
        $stmt = $db->query('SELECT id, name, price, description, image_url, stock FROM products ORDER BY id');
    }
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($products)):
    ?>
        <p>No products found<?php echo $search ? ' for "' . htmlspecialchars($search) . '"' : ''; ?>.</p>
    <?php else:
        foreach ($products as $product):
    ?>
        <div class="product">
            <a href="product.php?id=<?php echo $product['id']; ?>">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </a>
            <h3><a href="product.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></h3>
            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
            <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
            <?php if ($product['stock'] <= 0): ?>
                <p class="out-of-stock">Out of Stock</p>
            <?php elseif ($product['stock'] < 10): ?>
                <p class="low-stock">Only <?php echo $product['stock']; ?> left</p>
            <?php endif; ?>
            <form action="add_to_cart.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <label for="qty_<?php echo $product['id']; ?>">Qty:</label>
                <input type="number" name="quantity" id="qty_<?php echo $product['id']; ?>" value="1" min="1" max="<?php echo min(10, $product['stock']); ?>" style="width: 50px;">
                <input type="submit" value="Add to Cart" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
            </form>
        </div>
    <?php
        endforeach;
    endif;
    ?>
</div>

</body>
</html>
