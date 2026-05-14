<?php
require 'init.php';
require 'db.php';
init_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$password_confirm = trim($_POST['password_confirm'] ?? '');

// Validation
if (strlen($username) < 3 || strlen($username) > 20) {
    $_SESSION['error'] = 'Username must be 3-20 characters.';
    header('Location: register.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Invalid email address.';
    header('Location: register.php');
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['error'] = 'Password must be at least 6 characters.';
    header('Location: register.php');
    exit;
}

if ($password !== $password_confirm) {
    $_SESSION['error'] = 'Passwords do not match.';
    header('Location: register.php');
    exit;
}

// Check if username exists
$db = get_db();
$stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    $_SESSION['error'] = 'Username already exists.';
    header('Location: register.php');
    exit;
}

// Check if email exists
$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $_SESSION['error'] = 'Email already registered.';
    header('Location: register.php');
    exit;
}

// Create account with hashed password
try {
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$username, $hashed, $email, 'user']);

    $_SESSION['username'] = $username;
    $_SESSION['success'] = 'Account created successfully!';
    header('Location: index.php');
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = 'Registration failed. Please try again.';
    header('Location: register.php');
    exit;
}
