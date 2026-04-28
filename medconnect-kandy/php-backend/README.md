# LocalDoc Connect - PHP Application

Location-Based Medical Center Discovery & Appointment System - Kandy, Sri Lanka

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled (or Nginx)

## Installation

### 1. Database Setup

```bash
mysql -u root -p < database/schema.sql
```

### 2. Configure Database

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'medconnect_db');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
```

### 3. Seed Database

```bash
php seed_admin.php
php seed_clinics.php
```

### 4. Setup Web Server

**Apache:**
1. Place `php-backend` folder in web root (htdocs/www)
2. Ensure `mod_rewrite` is enabled
3. Access: `http://localhost/php-backend/`

**PHP Built-in Server:**
```bash
php -S localhost:8000
```

## Access Points

- **Patient Login:** `http://localhost/php-backend/`
- **Patient Register:** `http://localhost/php-backend/register.php`
- **Patient Dashboard:** `http://localhost/php-backend/dashboard.php`
- **Admin Login:** `http://localhost/php-backend/admin/login.php`
- **Admin Dashboard:** `http://localhost/php-backend/admin/dashboard.php`

## Default Admin Credentials

- **Email:** admin@localdoc.lk
- **Password:** LocalDocAdmin2024!

⚠️ Change this immediately in production!

## Project Structure

```
php-backend/
├── admin/                  # Admin panel
│   ├── login.php          # Admin login page
│   └── dashboard.php      # Admin dashboard
├── api/                   # REST API (optional)
├── config/
│   └── database.php       # Database configuration
├── helpers/
│   ├── security.php       # JWT & password hashing
│   └── functions.php      # Utility functions
├── middleware/
│   └── auth.php           # Auth middleware
├── database/
│   └── schema.sql         # Database schema
├── index.php              # Patient login page
├── register.php           # Patient registration
├── dashboard.php          # Patient dashboard
├── .htaccess              # Apache configuration
├── seed_admin.php         # Admin seeder
└── seed_clinics.php       # Clinics seeder
```

## Security Notes

1. Change `JWT_SECRET` in `config/database.php` for production
2. Use HTTPS in production
3. Enable CORS only for your frontend domain
4. Sanitize all inputs (already implemented)
5. Use prepared statements to prevent SQL injection (already implemented)

## Troubleshooting

### CORS Issues
Ensure your `.htaccess` file is loaded and `mod_headers` is enabled in Apache.

### 404 Errors
Check that `mod_rewrite` is enabled and `.htaccess` is being read by Apache.

### Database Connection Failed
Verify your database credentials in `config/database.php`.

### JWT Token Issues
Ensure the `Authorization` header is being sent correctly: `Bearer <token>`

## License

MIT
