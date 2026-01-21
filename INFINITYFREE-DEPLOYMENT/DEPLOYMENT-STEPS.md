# 🚀 InfinityFree Deployment - Step by Step Guide

## ✅ Complete Deployment Process

Follow these steps exactly to deploy on InfinityFree without errors!

---

## 📋 **STEP 1: Create InfinityFree Account**

1. Go to https://infinityfree.net
2. Click "Sign Up" (top right)
3. Fill in your details:
   - Email address
   - Password
   - Click "Create Account"
4. Verify your email
5. Login to your account

---

## 📋 **STEP 2: Create Website**

1. After login, click "Create Account" button
2. Fill in details:
   - **Username:** Choose a unique username (e.g., `achalshop`)
   - **Domain:** Choose one:
     - Free subdomain: `yourusername.infinityfreeapp.com`
     - Or use your own domain
   - **Password:** Create a strong password
3. Click "Create Account"
4. Wait 2-5 minutes for account activation
5. You'll receive email when ready

---

## 📋 **STEP 3: Setup Database**

### 3.1 Access cPanel
1. Go to Client Area → Your Website
2. Click "Control Panel" (cPanel)
3. Login with your credentials

### 3.2 Create MySQL Database
1. In cPanel, find "MySQL Databases"
2. Click on it
3. **Create Database:**
   - Database Name: `achalshop` (or any name)
   - Click "Create Database"
4. **Create Database User:**
   - Username: `achalshop_user`
   - Password: Generate strong password (save it!)
   - Click "Create User"
5. **Add User to Database:**
   - Select the database you created
   - Select the user you created
   - Check "ALL PRIVILEGES"
   - Click "Add"

### 3.3 Note Down Database Details
Save these details (you'll need them):
```
DB Host: sql123.infinityfree.com (check your cPanel for exact host)
DB Name: if0_12345678_achalshop (your actual database name)
DB User: if0_12345678 (your actual username)
DB Pass: your_password
```

---

## 📋 **STEP 4: Import Database Schema**

### 4.1 Access phpMyAdmin
1. In cPanel, find "phpMyAdmin"
2. Click on it
3. Select your database from left sidebar

### 4.2 Import SQL File
1. Click "Import" tab (top menu)
2. Click "Choose File"
3. Select `database.sql` from INFINITYFREE-DEPLOYMENT folder
4. Scroll down and click "Go"
5. Wait for success message
6. You should see tables created:
   - users
   - products
   - orders
   - order_items
   - addresses
   - sessions

### 4.3 Verify Data
1. Click on "users" table
2. You should see 3 default users:
   - Admin
   - Customer
   - Driver

---

## 📋 **STEP 5: Upload Files**

### 5.1 Access File Manager
1. In cPanel, find "File Manager"
2. Click on it
3. Navigate to `htdocs` folder (or `public_html`)

### 5.2 Delete Default Files
1. Select all files in htdocs
2. Click "Delete"
3. Confirm deletion

### 5.3 Upload Your Files
1. Click "Upload" button (top right)
2. Upload these files from INFINITYFREE-DEPLOYMENT folder:
   - `config.php`
   - `.htaccess`
   - Any other PHP files you have

**OR** Upload from FULL-SINGLE-FILES folder:
   - `customer_app_full.php` → rename to `customer.php`
   - `driver_app_full.php` → rename to `driver.php`
   - `admin_app_full.php` → rename to `admin.php`

3. Wait for upload to complete

### 5.4 Create Uploads Folder
1. Click "New Folder"
2. Name it: `uploads`
3. Right-click on `uploads` folder
4. Click "Change Permissions"
5. Set to `755` or `777`
6. Click "Change Permissions"

---

## 📋 **STEP 6: Configure Files**

### 6.1 Edit config.php
1. Right-click on `config.php`
2. Click "Edit"
3. Update these lines with your database details:

```php
define('DB_HOST', 'sql123.infinityfree.com'); // Your DB host
define('DB_NAME', 'if0_12345678_achalshop'); // Your DB name
define('DB_USER', 'if0_12345678'); // Your DB username
define('DB_PASS', 'your_password_here'); // Your DB password
define('BASE_URL', 'http://yourdomain.infinityfreeapp.com'); // Your domain
```

4. Click "Save Changes"
5. Close editor

### 6.2 Edit .htaccess (Optional)
1. Right-click on `.htaccess`
2. Click "Edit"
3. Find this line:
```apache
RewriteCond %{HTTP_REFERER} !^http(s)?://(www\.)?yourdomain\.com [NC]
```
4. Replace `yourdomain.com` with your actual domain
5. Click "Save Changes"

---

## 📋 **STEP 7: Test Your Website**

### 7.1 Access Homepage
1. Open browser
2. Go to: `http://yourdomain.infinityfreeapp.com`
3. You should see your website

### 7.2 Test Customer App
1. Go to: `http://yourdomain.infinityfreeapp.com/customer.php`
2. Try to register/login
3. Test features

### 7.3 Test Driver App
1. Go to: `http://yourdomain.infinityfreeapp.com/driver.php`
2. Login with:
   - Email: `driver@example.com`
   - Password: `Driver@123`

### 7.4 Test Admin Dashboard
1. Go to: `http://yourdomain.infinityfreeapp.com/admin.php`
2. Login with:
   - Email: `admin@achalmobileshop.com`
   - Password: `Admin@123`

---

## 📋 **STEP 8: Fix Common Errors**

### Error: "Database connection failed"
**Solution:**
1. Check config.php has correct database details
2. Verify database exists in phpMyAdmin
3. Check database user has ALL PRIVILEGES

### Error: "500 Internal Server Error"
**Solution:**
1. Check .htaccess file syntax
2. Rename .htaccess to .htaccess.bak temporarily
3. If site works, there's an issue in .htaccess
4. Remove problematic rules one by one

### Error: "Session failed"
**Solution:**
1. Make sure `sessions` table exists in database
2. Check config.php has session handler code
3. Clear browser cookies

### Error: "Upload failed"
**Solution:**
1. Check uploads folder exists
2. Set uploads folder permissions to 755 or 777
3. Verify file size is under 2MB

### Error: "Headers already sent"
**Solution:**
1. Make sure no output before `<?php`
2. Check for BOM in files (use Notepad++ to remove)
3. Ensure no spaces before `<?php` tag

---

## 📋 **STEP 9: Security Checklist**

After deployment, do these:

### 9.1 Change Default Passwords
1. Login to admin panel
2. Go to Settings → Change Password
3. Update admin password

### 9.2 Update Database Passwords
1. In phpMyAdmin, go to users table
2. Update passwords for default users
3. Or delete sample customer/driver accounts

### 9.3 Disable Error Display
1. In config.php, ensure:
```php
error_reporting(0);
ini_set('display_errors', 0);
```

### 9.4 Protect Sensitive Files
1. Verify .htaccess is protecting:
   - config.php
   - database.sql
   - error.log
   - activity.log

---

## 📋 **STEP 10: Optional Enhancements**

### 10.1 Add Custom Domain
1. Buy domain from Namecheap/GoDaddy
2. In InfinityFree, go to "Addon Domains"
3. Add your domain
4. Update nameservers at domain registrar

### 10.2 Enable SSL (HTTPS)
1. In cPanel, find "SSL/TLS"
2. Install free SSL certificate
3. In .htaccess, uncomment HTTPS redirect:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 10.3 Setup Email Forwarding
1. In cPanel, go to "Email Accounts"
2. Create email: admin@yourdomain.com
3. Forward to your Gmail

### 10.4 Add Google Analytics
1. Get tracking code from Google Analytics
2. Add to your PHP files before `</head>` tag

---

## 📋 **TROUBLESHOOTING**

### Website Not Loading?
1. Wait 5-10 minutes after account creation
2. Clear browser cache
3. Try incognito/private mode
4. Check if account is suspended (check email)

### Database Not Connecting?
1. Verify database credentials in config.php
2. Check database exists in phpMyAdmin
3. Ensure user has privileges
4. Try creating new database user

### Files Not Uploading?
1. Check file size (max 2MB on InfinityFree)
2. Use File Manager instead of FTP
3. Upload one file at a time
4. Check available disk space

### PHP Errors?
1. Check PHP version (should be 7.4 or 8.0)
2. Disable problematic PHP extensions
3. Use simpler code without external APIs
4. Check error.log file for details

---

## 📞 **NEED HELP?**

### If you're stuck:
1. **Check error.log file** in File Manager
2. **Screenshot the error** and send to me
3. **Tell me which step** you're stuck on
4. **Share your domain** so I can check

### Common Issues I Can Fix:
- Database connection errors
- File upload issues
- Session problems
- .htaccess errors
- PHP compatibility issues
- Any other deployment errors

---

## ✅ **DEPLOYMENT CHECKLIST**

Use this checklist to track your progress:

- [ ] Created InfinityFree account
- [ ] Created website/hosting account
- [ ] Created MySQL database
- [ ] Created database user
- [ ] Added user to database with privileges
- [ ] Imported database.sql via phpMyAdmin
- [ ] Verified tables created
- [ ] Uploaded all PHP files
- [ ] Created uploads folder
- [ ] Set folder permissions
- [ ] Edited config.php with database details
- [ ] Edited .htaccess with domain
- [ ] Tested homepage loads
- [ ] Tested customer app
- [ ] Tested driver app
- [ ] Tested admin dashboard
- [ ] Changed default admin password
- [ ] Disabled error display
- [ ] Verified .htaccess protection

---

## 🎉 **SUCCESS!**

If all steps completed:
- ✅ Your website is live!
- ✅ Database is working!
- ✅ All apps are functional!
- ✅ Security is configured!

**Your URLs:**
- Homepage: `http://yourdomain.infinityfreeapp.com`
- Customer: `http://yourdomain.infinityfreeapp.com/customer.php`
- Driver: `http://yourdomain.infinityfreeapp.com/driver.php`
- Admin: `http://yourdomain.infinityfreeapp.com/admin.php`

---

## 📱 **NEXT STEPS**

1. Customize your website
2. Add products
3. Test ordering process
4. Share with friends
5. Start your business! 🚀

---

**🎊 Congratulations on successful deployment!**

**Need modifications or facing errors? Let me know!**
