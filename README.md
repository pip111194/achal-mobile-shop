# 🛒 Achal Mobile Shop - Complete E-Commerce Ecosystem

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://www.php.net/)
[![Firebase](https://img.shields.io/badge/Firebase-Realtime%20DB-orange)](https://firebase.google.com/)

A complete e-commerce ecosystem with three interconnected Progressive Web Applications (PWAs):

- 📱 **Customer App** - Mobile-first shopping experience
- 🚗 **Driver App** - Delivery management system  
- 🖥️ **Admin Dashboard** - Complete business management

## 🌟 Features

### Customer App
- ✅ Email OTP Authentication
- ✅ Product Browsing & Search
- ✅ Shopping Cart & Wishlist
- ✅ Multiple Payment Methods (COD, UPI, Cards, Wallet)
- ✅ Real-time Order Tracking
- ✅ Google Maps Integration
- ✅ Wallet & Pay Later (₹100 limit)
- ✅ Referral Program
- ✅ PWA with Offline Support

### Driver App
- ✅ Real-time GPS Tracking
- ✅ Order Assignment & Management
- ✅ Turn-by-turn Navigation
- ✅ Earnings & Payouts
- ✅ Performance Analytics
- ✅ Photo Proof of Delivery
- ✅ OTP Verification

### Admin Dashboard
- ✅ Real-time Analytics
- ✅ Order Management
- ✅ Customer Management
- ✅ Driver Management
- ✅ Product Management
- ✅ Live Tracking Map
- ✅ Revenue Reports
- ✅ Content Management

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- Web server (Apache/Nginx)
- SSL certificate (required for PWA)
- Firebase account
- Google Maps API key

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/pip111194/achal-mobile-shop.git
cd achal-mobile-shop
```

2. **Choose your implementation**

#### Option A: Single-File Versions (Simplified)
```bash
cd option-a-single-files
# Upload customer_app.php, driver_app.php, admin_app.php to your server
```

#### Option B: Modular Structure (Production-Ready)
```bash
cd option-b-modular
# Upload entire folder structure to your server
```

#### Option C: Code Snippets & Documentation
```bash
cd option-c-documentation
# Follow the implementation guide
```

#### Option D: Complete Customer App (Fully Featured)
```bash
cd option-d-complete-customer
# Upload to your server
```

3. **Configure Firebase**

Edit the Firebase configuration in each app:

```javascript
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_AUTH_DOMAIN",
    databaseURL: "YOUR_DATABASE_URL",
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_STORAGE_BUCKET",
    messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
    appId: "YOUR_APP_ID"
};
```

4. **Configure Google Maps**

Replace the API key:
```javascript
const GOOGLE_MAPS_API_KEY = "YOUR_GOOGLE_MAPS_API_KEY";
```

5. **Configure Email SMTP**

Edit PHP email settings:
```php
$smtp_host = 'smtp.gmail.com';
$smtp_port = 465;
$username = 'your-email@gmail.com';
$password = 'your-app-password';
```

6. **Setup Master API Key**

The master API key for inter-app communication:
```php
$masterApiKey = 'achal_key-7i3ry8ioio2e3yfuu9ipo7uttrfew';
```

## 📁 Project Structure

```
achal-mobile-shop/
├── option-a-single-files/          # Simplified single PHP files
│   ├── customer_app.php            # ~3,000 lines
│   ├── driver_app.php              # ~2,800 lines
│   └── admin_app.php               # ~3,500 lines
│
├── option-b-modular/               # Production-ready modular structure
│   ├── customer/
│   ├── driver/
│   └── admin/
│
├── option-c-documentation/         # Detailed guides & snippets
│   ├── implementation-guide.md
│   ├── api-documentation.md
│   ├── database-schema.md
│   └── code-snippets/
│
├── option-d-complete-customer/     # Fully featured customer app
│   └── customer_app_complete.php   # ~15,000 lines
│
└── README.md
```

## 🔧 Configuration

### Firebase Realtime Database Rules

```json
{
  "rules": {
    "users": {
      "$uid": {
        ".read": "$uid === auth.uid",
        ".write": "$uid === auth.uid"
      }
    },
    "products": {
      ".read": true,
      ".write": "auth.uid !== null"
    },
    "orders": {
      "$orderId": {
        ".read": "auth.uid !== null",
        ".write": "auth.uid !== null"
      }
    },
    "drivers": {
      "$driverId": {
        ".read": "auth.uid !== null",
        ".write": "$driverId === auth.uid"
      }
    }
  }
}
```

### Apache .htaccess (for clean URLs)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
```

## 🎨 Design System

### Glassmorphism Theme

All apps use a consistent glassmorphism design:

- **Background**: Gradient overlays with blur effects
- **Cards**: Semi-transparent with backdrop filters
- **Buttons**: Gradient backgrounds with shadows
- **Colors**: 
  - Primary: `#FF6B35` to `#FFA726`
  - Secondary: `#667eea` to `#764ba2`
  - Success: `#00B894` to `#00D2A0`

## 📱 PWA Features

- ✅ Installable on mobile devices
- ✅ Offline functionality with Service Workers
- ✅ Push notifications
- ✅ App-like experience
- ✅ Fast loading with caching

## 🔐 Security Features

- ✅ Email OTP verification
- ✅ Firebase Authentication
- ✅ CSRF protection
- ✅ XSS prevention
- ✅ SQL injection prevention
- ✅ Secure session management
- ✅ API key authentication

## 📊 Database Schema

### Users Collection
```json
{
  "userId": {
    "name": "string",
    "email": "string",
    "phone": "string",
    "wallet": { "balance": 0 },
    "payLater": { "limit": 100, "used": 0 }
  }
}
```

### Orders Collection
```json
{
  "orderId": {
    "userId": "string",
    "items": [],
    "total": 0,
    "status": "pending|confirmed|delivered",
    "driverId": "string"
  }
}
```

### Products Collection
```json
{
  "productId": {
    "name": "string",
    "price": 0,
    "stock": 0,
    "images": []
  }
}
```

## 🚀 Deployment

### Recommended Hosting
- **Shared Hosting**: Hostinger, Bluehost, SiteGround
- **VPS**: DigitalOcean, Linode, Vultr
- **Cloud**: AWS, Google Cloud, Azure

### SSL Certificate
Required for PWA functionality. Use:
- Let's Encrypt (Free)
- Cloudflare SSL
- Paid SSL certificates

## 📖 Documentation

Detailed documentation available in `/option-c-documentation/`:

- [Implementation Guide](option-c-documentation/implementation-guide.md)
- [API Documentation](option-c-documentation/api-documentation.md)
- [Database Schema](option-c-documentation/database-schema.md)
- [Deployment Guide](option-c-documentation/deployment-guide.md)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Achal Mobile Shop Development Team**

## 🙏 Acknowledgments

- Firebase for real-time database
- Google Maps for location services
- All open-source contributors

## 📞 Support

For support, email: support@achalmobileshop.com

---

**⭐ Star this repository if you find it helpful!**

**🔗 Live Demo**: [Coming Soon]

**📱 Download Apps**: [Coming Soon]
