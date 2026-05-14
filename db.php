<?php

function get_db(): PDO {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . __DIR__ . '/data/store.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA journal_mode=WAL');
        $db->exec('PRAGMA foreign_keys=ON');
    }
    return $db;
}

function init_db(): void {
    $db = get_db();

    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    $db->exec('CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        price REAL NOT NULL,
        description TEXT,
        image_url TEXT,
        stock INTEGER DEFAULT 100,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    $db->exec('CREATE TABLE IF NOT EXISTS cart (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        product_id INTEGER NOT NULL,
        quantity INTEGER DEFAULT 1,
        FOREIGN KEY (username) REFERENCES users(username),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )');

    $db->exec('CREATE TABLE IF NOT EXISTS purchases (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        product_id INTEGER NOT NULL,
        quantity INTEGER DEFAULT 1,
        price REAL NOT NULL,
        total REAL NOT NULL,
        purchased_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (username) REFERENCES users(username),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )');

    // Seed default users (only if table is empty)
    $count = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count == 0) {
        $stmt = $db->prepare('INSERT INTO users (username, password, email) VALUES (?, ?, ?)');
        $stmt->execute(['admin', password_hash('admin123', PASSWORD_BCRYPT), 'admin@urbankicks.com']);
        $stmt->execute(['user',  password_hash('user123',  PASSWORD_BCRYPT), 'user@urbankicks.com']);
    }

    // Seed default products (only if table is empty)
    $count = $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($count == 0) {
        $products = [
            ['Nike Runner', 120, 'Classic running shoe with air cushioning', 'https://www.insidehook.com/wp-content/uploads/2024/03/Nike-Air-Max-Plus.jpg?fit=1200%2C800'],
            ['Air Force 1', 110, 'Iconic high-top basketball shoe', 'https://static.nike.com/a/images/t_PDP_1280_v1/f_auto,q_auto:eco/99486859-0b5f-4d38-b6a4-bb9e3e4d6b8e/air-force-1-07-shoes-6M4XhF.png'],
            ['Jordan 1 Mid', 130, 'Premium mid-top sneaker for collectors', 'https://static.nike.com/a/images/t_PDP_1280_v1/f_auto,q_auto:eco/b1f1b6a4-3e5b-4e5c-8e1f-0e5e5e5e5e5e/air-jordan-1-mid-shoes-5Z9h6K.png'],
            ['Adidas Superstar', 90, 'Classic street style sneaker', 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/8ea578f6c07847fba2d0ac85011d7f1f_9366/Superstar_Shoes_White_EG4958_01_standard.jpg'],
            ['Puma Future Rider', 95, 'Retro-inspired casual sneaker', 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa,w_600,h_600/global/370115/01-PNA-hover'],
            ['Converse Chuck Taylor', 65, 'Timeless canvas high-top', 'https://www.converse.com/dw/image/v2/BLTB_PRD_MP/cdn/asset/bltea78aab625b0bff8/08032022/Chuck_Taylor_All_Star_70_Hi_Vintage_Canvas_Black_1.png']
        ];
        $stmt = $db->prepare('INSERT INTO products (name, price, description, image_url) VALUES (?, ?, ?, ?)');
        foreach ($products as [$name, $price, $desc, $img]) {
            $stmt->execute([$name, $price, $desc, $img]);
        }
    }
}

// Ensure data directory exists
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0700, true);
}
