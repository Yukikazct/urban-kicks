<?php
require 'init.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['product_id'])) {
    header('Location: index.php');
    exit;
}

$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity'] ?? 1);

if ($quantity < 1 || $quantity > 10) {
    $quantity = 1;
}

$db = get_db();

// Check if product exists and has stock
$stmt = $db->prepare('SELECT id, stock FROM products WHERE id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    header('Location: index.php');
    exit;
}

if ($product['stock'] < $quantity) {
    $_SESSION['error'] = 'Not enough stock available.';
    header('Location: index.php');
    exit;
}

// Guest cart: store in session
if (!isset($_SESSION['username'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $existing = $_SESSION['cart'][$product_id] ?? 0;
    $_SESSION['cart'][$product_id] = min(10, $existing + $quantity);
    $_SESSION['error'] = 'Please login to save your cart permanently.';
    header('Location: login.php');
    exit;
}

// Logged-in user: upsert into DB cart
$stmt = $db->prepare('SELECT id, quantity FROM cart WHERE username = ? AND product_id = ?');
$stmt->execute([$_SESSION['username'], $product_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $newQty = min(10, $existing['quantity'] + $quantity);
    $stmt = $db->prepare('UPDATE cart SET quantity = ? WHERE id = ?');
    $stmt->execute([$newQty, $existing['id']]);
} else {
    $stmt = $db->prepare('INSERT INTO cart (username, product_id, quantity) VALUES (?, ?, ?)');
    $stmt->execute([$_SESSION['username'], $product_id, $quantity]);
}

header('Location: cart.php');
exit;
