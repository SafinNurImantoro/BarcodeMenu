# 🚀 QUICK START GUIDE

## Step 1: Update .env File
```bash
cd E:\Project Stassy\BARCODEMENU
# Edit .env dengan text editor
# Add your payment gateway credentials:
TRIPAY_PRIVATE_KEY=your_key_here
MIDTRANS_SERVER_KEY=your_key_here
```

## Step 2: Verify Database
```bash
mysql -u root -e "USE cafemenu; SELECT COUNT(*) FROM menu;"
# Should show: 23 (menu items)
```

## Step 3: Test Application
1. Buka browser: `http://localhost/BARCODEMENU/`
2. Verify menu muncul dengan benar
3. Test checkout form
4. Submit order & verify PDF generation

## Step 4: Check Security Logs
```bash
type logs\security.log
# Verify logging working
```

## Step 5: Verify Git Ignore
```bash
git status
# Verify .env NOT dalam staging area
```

---

## 📋 Important Files

| File | Purpose | Action |
|------|---------|--------|
| .env | Credentials | ⚠️ UPDATE with your keys |
| config.php | Configuration | ✅ Ready (no changes needed) |
| helpers.php | Validation | ✅ Ready (no changes needed) |
| .gitignore | Git ignore | ✅ Ready (prevents .env leak) |
| SECURITY_SETUP.md | Documentation | 📖 Read for details |

---

## 🔒 Security Summary

✅ SQL Injection - Protected
✅ XSS - Protected
✅ Credentials - Secured
✅ Input Validation - Implemented
✅ File Security - Implemented
✅ Logging - Implemented

---

## 🆘 Troubleshooting

### Database not found
```
Check: DB_* values di .env
Run: mysql -u root cafemenu < cafemenu.sql
```

### Menu not showing
```
Check: Browser console untuk errors
Check: logs/security.log untuk SQL errors
Verify: Database connection di db.php
```

### PDF not generating
```
Check: invoices/ folder exists
Run: mkdir invoices (jika perlu)
Verify: DOMPDF di vendor/dompdf/
```

### Access denied to .env
```
Good! Security headers working correctly.
Credentials tidak accessible dari browser.
```

---

## 📧 Support

Refer to SECURITY_SETUP.md untuk detailed instructions.

For issues, check:
1. logs/security.log (application logs)
2. Browser console (JavaScript errors)
3. Server error logs (PHP errors)
