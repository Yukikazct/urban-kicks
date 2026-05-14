<?php require 'init.php'; require 'db.php'; init_db(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Urban Kicks</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Urban Kicks</h1>
</header>

<?php include 'nav.php'; ?>

<div id="content">
    <h2>Create Account</h2>

    <?php
    if (isset($_SESSION['error'])) {
        echo '<p class="error">' . htmlspecialchars($_SESSION['error']) . '</p>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo '<p class="success">' . htmlspecialchars($_SESSION['success']) . '</p>';
        unset($_SESSION['success']);
    }
    ?>

    <form action="process_register.php" method="POST">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required minlength="3" maxlength="20">
        <br><br>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>
        <br><br>

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required minlength="6">
        <br><br>

        <label for="password_confirm">Confirm Password:</label>
        <input type="password" name="password_confirm" id="password_confirm" required minlength="6">
        <br><br>

        <input type="submit" value="Create Account">
    </form>

    <p style="margin-top: 20px;">Already have an account? <a href="login.php">Login here</a></p>
</div>

</body>
</html>
