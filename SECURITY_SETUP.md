# TEAZZI Cafe Menu - Security Configuration Guide

## ⚙️ Setup Instructions

### 1. Database Setup
- Import `cafemenu.sql` ke MySQL:
  ```bash
  mysql -u root cafemenu < cafemenu.sql
  ```

### 2. Environment Configuration
- Copy `.env.example` ke `.env` (atau edit `.env` yang sudah ada)
- Update nilai-nilai berikut sesuai kebutuhan:

```env
# Database
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=cafemenu

# Payment Gateway - Tripay
TRIPAY_PRIVATE_KEY=YOUR_PRIVATE_KEY
TRIPAY_MERCHANT_CODE=YOUR_MERCHANT_CODE
TRIPAY_API_KEY=YOUR_API_KEY

# Payment Gateway - Midtrans
MIDTRANS_SERVER_KEY=YOUR_SERVER_KEY
MIDTRANS_CLIENT_KEY=YOUR_CLIENT_KEY
MIDTRANS_API_URL=https://app.midtrans.com/snap/v1/transactions

# Application
APP_URL=http://localhost/BARCODEMENU
APP_ENV=development
```

### 3. Folder Permissions
Pastikan folder berikut memiliki write permission:
```bash
chmod 755 invoices/
chmod 755 logs/
```

### 4. Web Server Setup
- Arahkan DocumentRoot ke folder project
- Pastikan `.htaccess` enabled (Apache)
- Untuk Nginx, gunakan configuration yang sesuai

---

## 🔒 Security Features Implemented

### ✅ SQL Injection Prevention
- Semua query menggunakan **prepared statements**
- Parameter di-bind secara aman

### ✅ XSS (Cross-Site Scripting) Prevention
- Semua user input di-escape dengan `htmlspecialchars(ENT_QUOTES, 'UTF-8')`
- Output encoding sesuai context

### ✅ Input Validation & Sanitization
- Validasi server-side di `helpers.php`
- Phone number format validation (62...)
- Table number alphanumeric validation
- Customer name length & character validation
- Payment method whitelist validation

### ✅ Credentials Management
- Private key dan credentials di `.env` (tidak di-commit)
- Load credentials dari environment variables via `config.php`

### ✅ File Security
- PDF files dengan permission `0600` (owner read-only)
- Filename dengan random string untuk prevent guessing
- Session-based access tracking untuk invoices

### ✅ HTTP Security Headers
- X-Frame-Options: SAMEORIGIN (prevent clickjacking)
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- Content-Security-Policy enabled

### ✅ Sensitive File Protection
- `.env`, config files protected via `.htaccess`
- Directory listing disabled
- Logs directory tidak accessible via browser

### ✅ Error Handling
- Display errors disabled di production
- Error logging ke file (logs/security.log)
- User-friendly error messages

---

## 🚀 File Structure

```
BARCODEMENU/
├── .env                 # Environment variables (NOT in git)
├── .env.example        # Example env file
├── .gitignore          # Git ignore rules
├── .htaccess           # Apache security rules
├── config.php          # Configuration loader
├── db.php              # Database connection
├── helpers.php         # Validation & security functions
├── index.php           # Main menu page
├── checkout.php        # Checkout page
├── submit_order.php    # Order submission & PDF generation
├── admin.php           # Admin panel
├── composer.json       # PHP dependencies
├── vendor/             # Composer packages (DOMPDF)
├── assets/             # CSS, images
├── invoices/           # Generated PDF invoices (NOT in git)
├── logs/               # Application logs (NOT in git)
├── payment/            # Payment gateway integrations
│   ├── tripay_calledback.php
│   └── tripay_transaction.php
└── cafemenu.sql        # Database export
```

---

## 📋 Validation Rules

### Phone Number
- Format: `62` + minimal 9 digit + maksimal 13 digit
- Example: `6281234567890`

### Table Number
- Alphanumeric + hyphen + underscore
- Length: 1-20 character
- Example: `A-01`, `Table_5`

### Customer Name
- Length: 3-100 character
- Allow: letters, numbers, spaces, basic punctuation (. - ')
- Example: `John Doe`, `Budi S.`

### Payment Method
- Whitelist: `QRIS`, `Tunai`, `Transfer`

### Notes
- Max 500 character
- Remove potentially dangerous characters

---

## 🔧 Development Mode

Set `APP_ENV=development` di `.env` untuk:
- Display error details
- Enable error logging
- Security event logging

---

## ⚠️ Important Security Notes

1. **Never commit `.env` file** - Contains sensitive credentials
2. **Update credentials regularly** - Change payment gateway keys periodically
3. **Review logs** - Check `logs/security.log` untuk suspicious activities
4. **Backup invoices** - Folder `invoices/` contains customer data
5. **HTTPS only** - Use HTTPS di production (update APP_URL)
6. **Regular updates** - Update DOMPDF dan dependencies regularly

---

## 🐛 Troubleshooting

### Database connection fails
- Check `.env` DB_* values
- Verify MySQL is running
- Check user permissions

### PDF generation fails
- Check `invoices/` folder exists dan writable
- Verify DOMPDF di vendor folder
- Check error logs

### Payment gateway not working
- Update credentials di `.env`
- Check API endpoints
- Review callback validation

---

## 📧 Support
For security issues, contact the development team.

