<?php
require 'init.php';
require 'db.php';
init_db();

$db = get_db();
$message = '';
$error = '';

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check for logged-in actions
    if (isset($_SESSION['username'])) {
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $error = 'Invalid request.';
        }
    }

    if (!$error) {
        // Update quantity
        if (isset($_POST['update_qty']) && isset($_SESSION['username'])) {
            $cart_id = intval($_POST['cart_id']);
            $qty = intval($_POST['quantity']);
            if ($qty < 1) $qty = 1;
            if ($qty > 10) $qty = 10;

            $stmt = $db->prepare('UPDATE cart SET quantity = ? WHERE id = ? AND username = ?');
            $stmt->execute([$qty, $cart_id, $_SESSION['username']]);
            header('Location: cart.php');
            exit;
        }

        // Remove single item
        if (isset($_POST['remove']) && isset($_SESSION['username'])) {
            $cart_id = intval($_POST['cart_id']);
            $stmt = $db->prepare('DELETE FROM cart WHERE id = ? AND username = ?');
            $stmt->execute([$cart_id, $_SESSION['username']]);
            header('Location: cart.php');
            exit;
        }

        // Clear cart
        if (isset($_POST['clear'])) {
            if (isset($_SESSION['username'])) {
                $stmt = $db->prepare('DELETE FROM cart WHERE username = ?');
                $stmt->execute([$_SESSION['username']]);
            } else {
                $_SESSION['cart'] = [];
            }
            header('Location: cart.php');
            exit;
        }

        // Checkout
        if (isset($_POST['checkout']) && isset($_SESSION['username'])) {
            $stmt = $db->prepare('SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.stock
                                  FROM cart c JOIN products p ON c.product_id = p.id
                                  WHERE c.username = ?');
            $stmt->execute([$_SESSION['username']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($items) {
                $db->beginTransaction();
                try {
                    $insert = $db->prepare('INSERT INTO purchases (username, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)');
                    $updateStock = $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');

                    foreach ($items as $item) {
                        if ($item['stock'] < $item['quantity']) {
                            throw new Exception("Not enough stock for {$item['name']}.");
                        }
                        $total = $item['price'] * $item['quantity'];
                        $insert->execute([$_SESSION['username'], $item['product_id'], $item['quantity'], $item['price'], $total]);
                        $updateStock->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
                    }

                    $stmt = $db->prepare('DELETE FROM cart WHERE username = ?');
                    $stmt->execute([$_SESSION['username']]);
                    $db->commit();
                    $message = 'Checkout successful! Your order has been placed.';
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = $e->getMessage();
                }
            }
        }
    }
}

// --- Load cart ---
$cartItems = [];
$grandTotal = 0;
if (isset($_SESSION['username'])) {
    $stmt = $db->prepare('SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image_url, p.stock
                          FROM cart c JOIN products p ON c.product_id = p.id
                          WHERE c.username = ?');
    $stmt->execute([$_SESSION['username']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cartItems as $item) {
        $grandTotal += $item['price'] * $item['quantity'];
    }
} else {
    // Guest cart: product_id => quantity
    $guestCart = $_SESSION['cart'] ?? [];
    if (!empty($guestCart)) {
        $ids = array_keys($guestCart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT id, name, price, image_url, stock FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as $p) {
            $qty = $guestCart[$p['id']];
            $cartItems[] = [
                'id' => 0,
                'product_id' => $p['id'],
                'quantity' => $qty,
                'name' => $p['name'],
                'price' => $p['price'],
                'image_url' => $p['image_url'],
                'stock' => $p['stock'],
            ];
            $grandTotal += $p['price'] * $qty;
        }
    }
}

// --- Load purchase history ---
$history = [];
if (isset($_SESSION['username'])) {
    $stmt = $db->prepare('SELECT p.name, p.image_url, pu.quantity, pu.price, pu.total, pu.purchased_at
                          FROM purchases pu JOIN products p ON pu.product_id = p.id
                          WHERE pu.username = ? ORDER BY pu.purchased_at DESC');
    $stmt->execute([$_SESSION['username']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Urban Kicks</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Urban Kicks</h1>
</header>

<?php include 'nav.php'; ?>

<div id="content">
    <h2>Shopping Cart</h2>

    <?php if ($message): ?>
        <p class="success"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <div class="cart-list">
        <?php foreach ($cartItems as $item): ?>
            <div class="cart-item-row">
                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-thumb">
                <div class="cart-item-info">
                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                    <span class="cart-price">$<?php echo number_format($item['price'], 2); ?> each</span>
                    <?php if ($item['stock'] < 10): ?>
                        <span class="low-stock">Only <?php echo $item['stock']; ?> left</span>
                    <?php endif; ?>
                </div>
                <div class="cart-item-actions">
                    <?php if (isset($_SESSION['username']) && $item['id'] > 0): ?>
                    <form action="cart.php" method="POST" class="qty-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo min(10, $item['stock']); ?>" class="qty-input">
                        <input type="submit" name="update_qty" value="Update" class="btn-small">
                    </form>
                    <form action="cart.php" method="POST" class="remove-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                        <input type="submit" name="remove" value="Remove" class="btn-small btn-danger">
                    </form>
                    <?php else: ?>
                        <span class="cart-qty">Qty: <?php echo $item['quantity']; ?></span>
                    <?php endif; ?>
                    <span class="cart-subtotal">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <div class="cart-total">
            <strong>Total: $<?php echo number_format($grandTotal, 2); ?></strong>
        </div>

        <div class="cart-buttons">
            <form action="cart.php" method="POST" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="clear" value="1">
                <input type="submit" value="Clear Cart">
            </form>

            <?php if (isset($_SESSION['username'])): ?>
                <form action="cart.php" method="POST" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="checkout" value="1">
                    <input type="submit" value="Checkout" class="btn-primary">
                </form>
            <?php else: ?>
                <p class="login-hint">Please <a href="login.php">login</a> to checkout.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <p style="margin-top:20px;"><a href="index.php">&larr; Continue Shopping</a></p>

    <?php if (!empty($history)): ?>
        <h2 style="margin-top:40px;">Purchase History</h2>
        <div class="history-list">
        <?php foreach ($history as $row): ?>
            <div class="cart-item-row">
                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="cart-thumb">
                <div class="cart-item-info">
                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                    <span class="cart-price">$<?php echo number_format($row['price'], 2); ?> x <?php echo $row['quantity']; ?></span>
                </div>
                <div class="cart-item-actions">
                    <span class="cart-subtotal">$<?php echo number_format($row['total'], 2); ?></span>
                    <span class="purchase-date"><?php echo htmlspecialchars($row['purchased_at']); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
