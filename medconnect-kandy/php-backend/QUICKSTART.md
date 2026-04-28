# Quick Start Guide

## 1. Setup Database
```bash
mysql -u root -p < database/schema.sql
```

## 2. Configure Database
Edit `config/database.php` with your MySQL credentials:
```php
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

## 3. Seed Data
```bash
php seed_admin.php
php seed_clinics.php
```

## 4. Start Server
```bash
php -S localhost:8000
```

## 5. Access Application
- Patient Login: http://localhost:8000
- Admin Login: http://localhost:8000/admin/login.php

## Default Admin
- Email: admin@localdoc.lk
- Password: LocalDocAdmin2024!
