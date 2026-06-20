# BD-Fashion Deployment Guide

## Prerequisites
- Windows with XAMPP, WAMP, or PHP-enabled web server
- MySQL / MariaDB
- PHP 7.4 or newer
- Optional: PHP CLI for syntax checks (`php -l`)

## Setup
1. Copy the project folder into your web root, for example:
   - `C:\xampp\htdocs\BD-Fashion`
2. Create a new MySQL database, for example `bd_fashion`.
3. Import the schema:
   - Use phpMyAdmin or MySQL CLI to import `sql/schema.sql`.
4. Update `config/config.php` with your database credentials.
5. Set your Stripe keys in `config/config.php` or via `admin/settings.php` after login.

## Running locally
- Start Apache and MySQL from XAMPP.
- Open the site at:
  - `http://localhost/BD-Fashion/`

## Admin pages
- `admin/login.php` — admin login
- `admin/logout.php` — admin logout
- `admin/index.php` — dashboard entry
- `admin/products.php` — manage products
- `admin/coupons.php` — manage coupons
- `admin/settings.php` — payment, currency, tax settings
- `admin/tickets.php` — support ticket inbox
- `admin/users.php` — user management
- `admin/reports.php` — sales analytics

## Admin account setup
- Default admin login after schema import:
  - username: `admin`
  - password: `Admin@123`

If no admin user exists, create one manually in the `admins` table. Use a PHP helper or other secure method to generate a password hash:
If no admin user exists, create one manually in the `admins` table. Use a PHP helper or other secure method to generate a password hash:

```php
<?php
echo password_hash('YourSecurePassword', PASSWORD_DEFAULT);
```

Then insert the admin record in MySQL, for example:

```sql
INSERT INTO admins (username, password_hash, role) VALUES ('admin', '<hashed-password>', 'admin');
```

## Notes
- The admin area is scaffolded and does not yet include a secure admin login page.
- The user-facing checkout flow uses Stripe Checkout when `payment_gateway` is configured as `stripe`.
- File downloads are protected by order ownership checks in `download.php`.
- Replace `assets/img/placeholder.png` with real product/media assets.

## Testing
- Verify pages in a browser for responsive layout.
- Use `php -l` on PHP files if PHP CLI is available:
  - `php -l file.php`
- If PHP is not available on the command line, use your web server logs and browser output for validation.
