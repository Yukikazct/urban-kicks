<div id="nav">
    <a href="index.php">Home</a>
    <a href="gallery.php">Gallery</a>
    <a href="cart.php">Cart</a>
    <?php if (isset($_SESSION['username'])): ?>
        <span class="user-status">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <a href="admin.php">Admin</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    <?php endif; ?>
</div>
