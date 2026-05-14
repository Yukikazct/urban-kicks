# Urban Kicks

A simple PHP sneaker e-commerce demo site with SQLite storage, user authentication, shopping cart, and purchase history.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8+ (procedural, no framework) |
| Database | SQLite via PDO (WAL mode, foreign keys) |
| Frontend | HTML + CSS (no JavaScript) |
| Auth | Session-based with bcrypt password hashing |

## Project Structure

```
├── style.css            # Global stylesheet
├── init.php             # Session init (30-day cookie)
├── db.php               # SQLite connection + schema + seed data
├── nav.php              # Navigation bar component
├── index.php            # Home page with product listing + search
├── product.php          # Product detail page
├── gallery.php          # Product gallery (database-driven)
├── login.php            # Login form
├── verify.php           # Login verification + guest cart merge
├── logout.php           # Logout
├── register.php         # Registration form
├── process_register.php # Registration validation + account creation
├── cart.php             # Shopping cart + checkout + purchase history
├── add_to_cart.php      # Add to cart (guest + logged-in users)
└── data/
    └── store.db         # SQLite database (auto-created)
```

## Features

| Feature | Description |
|---------|-------------|
| User Registration | Form validation, duplicate check, bcrypt password hashing |
| User Login/Logout | Session-based auth with 30-day persistence |
| Product Listing | Dynamic from database, with search/filter |
| Product Detail | Full product page with image, description, stock status |
| Shopping Cart | DB-backed for logged-in users, session-backed for guests |
| Guest Cart Merge | Guest items merge into user cart on login |
| Checkout | Cart items converted to purchases, stock decremented |
| Purchase History | Order history with product details and timestamps |
| Stock Management | Real-time stock tracking, out-of-stock prevention |
| Gallery | Database-driven product gallery |

## Getting Started

```bash
php -S localhost:8080
```

Then visit http://localhost:8080

## Test Accounts

| Username | Password |
|----------|----------|
| admin | admin123 |
| user | user123 |

## Security Notes

- Passwords are hashed with bcrypt
- CSRF protection on cart actions
- SQL injection prevention via PDO prepared statements
- XSS prevention via htmlspecialchars() on output
- This is a demo project — not production-hardened
