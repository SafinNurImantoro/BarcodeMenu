# 🔐 Database Admin Setup Guide

## ⚠️ PENTING: Login sudah dipindahkan ke DATABASE

Admin credentials sekarang disimpan di **database**, bukan di file `.env`.

## 🚀 Setup One-Time (Jalankan Sekali Saja)

### Cara 1: Menggunakan Web Interface (Rekomendasi)

1. **Buka di browser:**
   ```
   http://localhost/BARCODEMENU/setup_admin.php
   ```

2. **Klik tombol "Setup" atau biarkan page reload**
   - Sistem akan otomatis membuat table `admins` di database
   - Sistem akan otomatis insert admin user: `admin` / `admin123`

3. **Lihat hasil:**
   - Jika berhasil, akan ada pesan: ✅ Setup Complete!
   - Admin user sudah siap di database

### Cara 2: Menggunakan phpMyAdmin

1. Login ke phpMyAdmin
2. Pilih database: `cafemenu`
3. Klik tab "SQL"
4. Paste konten dari file: `cafemenu.sql` (admin table sudah included)
5. Klik "Execute"

### Cara 3: Menggunakan MySQL Console

```bash
cd "e:\Project Stassy\BARCODEMENU"
mysql -u root -p cafemenu < cafemenu.sql
```

Atau jika ada password di user root:
```bash
mysql -u root -pYOUR_PASSWORD cafemenu < cafemenu.sql
```

## 📋 Database Credentials

Edit `.env` jika diperlukan:
```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=cafemenu
```

## 🔑 Login Credentials

Setelah setup, gunakan:
```
Username: admin
Password: admin123
```

## ✅ Verify Setup

1. Buka: `http://localhost/BARCODEMENU/test_login.php`
   - Klik "Test Login" dengan username/password di atas
   - Jika berhasil, akan ada pesan: ✅ LOGIN SUCCESS!

2. Jika gagal, cek:
   - Database `cafemenu` ada? 
   - User `root` bisa connect?
   - Table `admins` dibuat?
   - Admin user ada di table?

## 🎯 Final Login

Setelah verify berhasil, buka:
```
http://localhost/BARCODEMENU/admin_login.php
```

Login dengan:
- Username: `admin`
- Password: `admin123`

## 🗄️ Database Structure

```sql
CREATE TABLE `admins` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(50) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100),
  `role` varchar(50) DEFAULT 'admin',
  `is_active` tinyint DEFAULT 1,
  `last_login` datetime,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime ON UPDATE CURRENT_TIMESTAMP
);
```

## 🔒 Security Notes

- Password di-hash dengan `password_hash()` (bcrypt)
- Tidak disimpan dalam plain text
- Session di-regenerate setalah login
- Rate limiting aktif (5 attempts, 2 menit timeout)
- last_login tracked untuk audit

## 🆘 Troubleshooting

### Error: "Database connection failed"
- Check database credentials di `.env`
- Pastikan MySQL running
- Pastikan database `cafemenu` sudah di-create

### Error: "Admin user not found"
- Run `setup_admin.php` lagi
- Verify table `admins` ada di database
- Cek apakah user `admin` ada di table

### Error: "is_active" field doesn't exist
- Table sudah ada tapi struktur lama
- Delete table lama: `DROP TABLE admins;`
- Run `setup_admin.php` lagi

### Password tetap salah
- Harus PERSIS: `admin123` (case-sensitive)
- Jika ingin ganti password, run `setup_admin.php` dengan password baru
- Atau update langsung di database:
  ```sql
  UPDATE admins SET password = '<hash>' WHERE username = 'admin';
  ```

---

**Last Updated:** 2026-02-13
**Status:** Database-based authentication is now active
