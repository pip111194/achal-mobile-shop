# Option A: Simplified Single-File Versions

This folder contains simplified, production-ready single PHP files for all three applications.

## 📁 Files

- **customer_app.php** (~3,000 lines) - Customer-facing shopping app
- **driver_app.php** (~2,800 lines) - Driver delivery management
- **admin_app.php** (~3,500 lines) - Admin dashboard

## ✨ Features Included

### Customer App
✅ Email OTP Authentication  
✅ Firebase Integration  
✅ Glassmorphism UI  
✅ Login/Register Forms  
✅ Google Sign-In  
✅ Toast Notifications  
✅ PWA Support  
✅ Responsive Design  

### Driver App
✅ Driver Authentication  
✅ GPS Tracking  
✅ Order Management  
✅ Earnings Dashboard  
✅ Real-time Updates  

### Admin App
✅ Admin Authentication  
✅ Dashboard Analytics  
✅ Order Management  
✅ User Management  
✅ Product Management  

## 🚀 Quick Setup

1. **Upload Files**
   ```bash
   # Upload to your web server
   scp customer_app.php user@server:/var/www/html/
   scp driver_app.php user@server:/var/www/html/
   scp admin_app.php user@server:/var/www/html/
   ```

2. **Configure Firebase**
   
   Edit the Firebase config in each file:
   ```javascript
   const firebaseConfig = {
       apiKey: "YOUR_API_KEY",
       authDomain: "YOUR_AUTH_DOMAIN",
       // ... rest of config
   };
   ```

3. **Configure Email SMTP**
   
   Edit PHP email settings:
   ```php
   $smtp_username = 'your-email@gmail.com';
   $smtp_password = 'your-app-password';
   ```

4. **Access Your Apps**
   - Customer: `https://yourdomain.com/customer_app.php`
   - Driver: `https://yourdomain.com/driver_app.php`
   - Admin: `https://yourdomain.com/admin_app.php`

## 📋 Requirements

- PHP 7.4+
- SSL Certificate (for PWA)
- Firebase Account
- Google Maps API Key
- SMTP Email Account

## 🎨 Customization

All styling is embedded in the files. Look for the `<style>` section to customize:

- Colors (CSS variables)
- Fonts
- Layouts
- Animations

## 🔒 Security Notes

1. Change the master API key
2. Use environment variables for sensitive data
3. Enable HTTPS
4. Configure Firebase security rules
5. Implement rate limiting

## 📱 PWA Setup

Each app includes PWA capabilities. To enable:

1. Create `manifest.json`
2. Create `sw.js` (Service Worker)
3. Add app icons (192x192, 512x512)
4. Serve over HTTPS

## 🐛 Troubleshooting

**OTP not sending?**
- Check SMTP credentials
- Enable "Less secure app access" in Gmail
- Or use App Password

**Firebase not connecting?**
- Verify API keys
- Check Firebase console
- Enable Authentication methods

**PWA not installing?**
- Ensure HTTPS is enabled
- Check manifest.json
- Verify Service Worker registration

## 📞 Support

For issues or questions, please open an issue on GitHub.

## 📄 License

MIT License - See main repository for details.
