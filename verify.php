<?php
require 'init.php';
require 'db.php';
init_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['error'] = 'Username and password are required.';
    header('Location: login.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare('SELECT password, role FROM users WHERE username = ?');
$stmt->execute([$username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !password_verify($password, $row['password'])) {
    $_SESSION['error'] = 'Invalid username or password.';
    header('Location: login.php');
    exit;
}

$_SESSION['username'] = $username;
$_SESSION['role'] = $row['role'];

// Merge guest cart into user's DB cart
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $productId => $qty) {
        // Upsert: if product already in user's cart, update quantity
        $stmt = $db->prepare('SELECT id, quantity FROM cart WHERE username = ? AND product_id = ?');
        $stmt->execute([$username, $productId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $newQty = min(10, $existing['quantity'] + $qty);
            $stmt = $db->prepare('UPDATE cart SET quantity = ? WHERE id = ?');
            $stmt->execute([$newQty, $existing['id']]);
        } else {
            $stmt = $db->prepare('INSERT INTO cart (username, product_id, quantity) VALUES (?, ?, ?)');
            $stmt->execute([$username, $productId, $qty]);
        }
    }
    unset($_SESSION['cart']);
}

header('Location: index.php');
exit;
