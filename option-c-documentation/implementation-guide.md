# 📚 Complete Implementation Guide

## Table of Contents
1. [System Overview](#system-overview)
2. [Prerequisites](#prerequisites)
3. [Firebase Setup](#firebase-setup)
4. [Google Maps Setup](#google-maps-setup)
5. [Email Configuration](#email-configuration)
6. [Database Schema](#database-schema)
7. [API Integration](#api-integration)
8. [Deployment](#deployment)
9. [Testing](#testing)

---

## System Overview

The Achal Mobile Shop ecosystem consists of three interconnected Progressive Web Applications:

```
┌─────────────────┐
│  Customer App   │ ←──┐
│  (Mobile PWA)   │    │
└─────────────────┘    │
                       │
┌─────────────────┐    │    ┌──────────────────┐
│   Driver App    │ ←──┼───→│  Firebase RTDB   │
│  (Mobile PWA)   │    │    │  (Central Store) │
└─────────────────┘    │    └──────────────────┘
                       │
┌─────────────────┐    │
│  Admin Dashboard│ ←──┘
│  (Desktop Web)  │
└─────────────────┘
```

### Communication Flow

```
Customer → Places Order → Firebase → Admin Dashboard
                                  ↓
                            Assigns Driver
                                  ↓
                          Driver App → Accepts
                                  ↓
                          Real-time Tracking
                                  ↓
                          Customer + Admin
```

---

## Prerequisites

### Server Requirements
- **PHP**: 7.4 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **SSL Certificate**: Required for PWA
- **Memory**: Minimum 512MB RAM
- **Storage**: Minimum 1GB free space

### External Services
- Firebase Account (Free tier sufficient for testing)
- Google Cloud Account (for Maps API)
- Gmail Account (for SMTP)

### Development Tools
- Text Editor (VS Code recommended)
- Git
- Browser with DevTools
- Postman (for API testing)

---

## Firebase Setup

### Step 1: Create Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click "Add Project"
3. Enter project name: `achal-mobile-shop`
4. Disable Google Analytics (optional)
5. Click "Create Project"

### Step 2: Enable Authentication

1. In Firebase Console, go to **Authentication**
2. Click "Get Started"
3. Enable **Email/Password**:
   - Click "Email/Password"
   - Toggle "Enable"
   - Click "Save"
4. Enable **Google Sign-In**:
   - Click "Google"
   - Toggle "Enable"
   - Enter support email
   - Click "Save"

### Step 3: Setup Realtime Database

1. Go to **Realtime Database**
2. Click "Create Database"
3. Choose location (closest to your users)
4. Start in **Test Mode** (for development)
5. Click "Enable"

### Step 4: Configure Security Rules

Replace default rules with:

```json
{
  "rules": {
    "users": {
      "$uid": {
        ".read": "$uid === auth.uid || root.child('admins').child(auth.uid).exists()",
        ".write": "$uid === auth.uid || root.child('admins').child(auth.uid).exists()"
      }
    },
    "drivers": {
      "$driverId": {
        ".read": "auth.uid !== null",
        ".write": "$driverId === auth.uid || root.child('admins').child(auth.uid).exists()",
        "location": {
          ".write": "$driverId === auth.uid"
        }
      }
    },
    "products": {
      ".read": true,
      ".write": "root.child('admins').child(auth.uid).exists()"
    },
    "orders": {
      "$orderId": {
        ".read": "auth.uid !== null",
        ".write": "auth.uid !== null"
      }
    },
    "admins": {
      ".read": "root.child('admins').child(auth.uid).exists()",
      ".write": "root.child('admins').child(auth.uid).exists()"
    }
  }
}
```

### Step 5: Setup Firebase Storage

1. Go to **Storage**
2. Click "Get Started"
3. Start in **Test Mode**
4. Click "Done"

### Step 6: Configure Storage Rules

```
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    match /products/{productId}/{allPaths=**} {
      allow read: if true;
      allow write: if request.auth != null;
    }
    match /users/{userId}/{allPaths=**} {
      allow read, write: if request.auth.uid == userId;
    }
    match /drivers/{driverId}/{allPaths=**} {
      allow read: if request.auth != null;
      allow write: if request.auth.uid == driverId;
    }
  }
}
```

### Step 7: Get Configuration

1. Go to **Project Settings** (gear icon)
2. Scroll to "Your apps"
3. Click web icon (</>)
4. Register app: `Achal Mobile Shop`
5. Copy the `firebaseConfig` object:

```javascript
const firebaseConfig = {
  apiKey: "AIzaSyBbTaQvWY9Z1DfXI8SGXQlfzplPFe3TLPg",
  authDomain: "mobile-shop-e8bd6.firebaseapp.com",
  databaseURL: "https://mobile-shop-e8bd6-default-rtdb.firebaseio.com",
  projectId: "mobile-shop-e8bd6",
  storageBucket: "mobile-shop-e8bd6.firebasestorage.app",
  messagingSenderId: "903890636840",
  appId: "1:903890636840:web:29d9a6cd9be6526638a51d"
};
```

---

## Google Maps Setup

### Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create new project: `Achal Mobile Shop`
3. Enable billing (required for Maps API)

### Step 2: Enable APIs

Enable these APIs:
- Maps JavaScript API
- Geocoding API
- Directions API
- Distance Matrix API
- Places API

### Step 3: Create API Key

1. Go to **APIs & Services** → **Credentials**
2. Click "Create Credentials" → "API Key"
3. Copy the API key
4. Click "Restrict Key"

### Step 4: Restrict API Key

**Application Restrictions:**
- HTTP referrers (websites)
- Add your domains:
  ```
  https://yourdomain.com/*
  https://www.yourdomain.com/*
  ```

**API Restrictions:**
- Restrict key to selected APIs
- Select all Maps APIs enabled above

### Step 5: Test API Key

```html
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places"></script>
```

---

## Email Configuration

### Option 1: Gmail SMTP (Recommended for Testing)

1. **Enable 2-Factor Authentication**
   - Go to Google Account settings
   - Security → 2-Step Verification
   - Enable it

2. **Generate App Password**
   - Security → App passwords
   - Select app: Mail
   - Select device: Other (Custom name)
   - Enter: "Achal Mobile Shop"
   - Copy the 16-character password

3. **PHP Configuration**
```php
$smtp_host = 'smtp.gmail.com';
$smtp_port = 465; // or 587 for TLS
$smtp_username = 'your-email@gmail.com';
$smtp_password = 'your-16-char-app-password';
```

### Option 2: SendGrid (Recommended for Production)

1. Sign up at [SendGrid](https://sendgrid.com/)
2. Create API Key
3. Verify sender email
4. Use SendGrid PHP library:

```php
require 'vendor/autoload.php';

$email = new \SendGrid\Mail\Mail();
$email->setFrom("noreply@achalmobileshop.com", "Achal Mobile Shop");
$email->setSubject("Your OTP");
$email->addTo($to);
$email->addContent("text/html", $message);

$sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
$response = $sendgrid->send($email);
```

### Email Template

```html
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        .header {
            background: linear-gradient(135deg, #FF6B35 0%, #FFA726 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .content {
            padding: 40px;
            text-align: center;
        }
        .otp {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 10px;
            margin: 30px 0;
            padding: 20px;
            background: #f0f0f0;
            border-radius: 12px;
        }
        .footer {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 Achal Mobile Shop</h1>
        </div>
        <div class="content">
            <h2>Verify Your Email</h2>
            <p>Your One-Time Password (OTP) is:</p>
            <div class="otp">{{OTP}}</div>
            <p>This OTP is valid for <strong>10 minutes</strong>.</p>
            <p>If you didn't request this, please ignore this email.</p>
        </div>
        <div class="footer">
            <p>© 2024 Achal Mobile Shop. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
```

---

## Database Schema

### Users Collection

```json
{
  "users": {
    "userId123": {
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+919876543210",
      "profilePhoto": "https://...",
      "emailVerified": true,
      "phoneVerified": true,
      "addresses": {
        "addr1": {
          "type": "home",
          "name": "John Doe",
          "phone": "+919876543210",
          "addressLine1": "123 Main St",
          "addressLine2": "Apt 4B",
          "landmark": "Near Park",
          "city": "Mumbai",
          "state": "Maharashtra",
          "pincode": "400001",
          "location": {
            "lat": 19.0760,
            "lng": 72.8777
          },
          "isDefault": true
        }
      },
      "wallet": {
        "balance": 500,
        "transactions": {
          "txn1": {
            "type": "credit",
            "amount": 500,
            "description": "Referral bonus",
            "timestamp": 1234567890,
            "closingBalance": 500
          }
        }
      },
      "payLater": {
        "limit": 100,
        "used": 0,
        "dueDate": null
      },
      "referralCode": "ACHAL1234",
      "referredBy": "ACHAL5678",
      "referrals": {
        "user456": {
          "timestamp": 1234567890,
          "orderCompleted": true,
          "bonusEarned": 50
        }
      },
      "wishlist": {
        "prod1": true,
        "prod2": true
      },
      "cart": {
        "prod1": {
          "quantity": 2,
          "variant": "128GB",
          "addedAt": 1234567890
        }
      },
      "createdAt": 1234567890,
      "lastLogin": 1234567890
    }
  }
}
```

### Products Collection

```json
{
  "products": {
    "prod123": {
      "name": "iPhone 15 Pro",
      "sku": "IPH15PRO128",
      "brand": "Apple",
      "category": "smartphones",
      "subcategory": "premium",
      "description": "Latest iPhone with A17 Pro chip",
      "shortDescription": "Premium smartphone",
      "images": [
        "https://...",
        "https://..."
      ],
      "video": "https://youtube.com/...",
      "price": {
        "mrp": 134900,
        "selling": 129900,
        "discount": 5000,
        "discountPercent": 3.7
      },
      "stock": 50,
      "lowStockThreshold": 10,
      "variants": {
        "storage": ["128GB", "256GB", "512GB"],
        "color": ["Natural Titanium", "Blue Titanium"]
      },
      "specifications": {
        "display": "6.1-inch Super Retina XDR",
        "processor": "A17 Pro chip",
        "ram": "8GB",
        "storage": "128GB",
        "camera": "48MP Main + 12MP Ultra Wide",
        "battery": "3274 mAh",
        "os": "iOS 17"
      },
      "features": [
        "5G Support",
        "Face ID",
        "Wireless Charging",
        "Water Resistant"
      ],
      "warranty": "1 Year Apple Warranty",
      "returnPolicy": "30 days return",
      "shipping": {
        "weight": 0.5,
        "dimensions": {
          "length": 15,
          "width": 8,
          "height": 2
        },
        "freeShipping": true
      },
      "seo": {
        "metaTitle": "iPhone 15 Pro - Buy Online",
        "metaDescription": "...",
        "keywords": ["iphone", "apple", "smartphone"]
      },
      "status": "enabled",
      "featured": true,
      "newArrival": true,
      "bestSeller": true,
      "rating": {
        "average": 4.5,
        "count": 150
      },
      "reviews": {
        "rev1": {
          "userId": "user123",
          "rating": 5,
          "title": "Excellent phone",
          "comment": "Best phone ever!",
          "images": ["https://..."],
          "helpful": 25,
          "timestamp": 1234567890
        }
      },
      "createdAt": 1234567890,
      "updatedAt": 1234567890
    }
  }
}
```

### Orders Collection

```json
{
  "orders": {
    "order123": {
      "orderId": "ORD20240115001",
      "userId": "user123",
      "items": [
        {
          "productId": "prod123",
          "name": "iPhone 15 Pro",
          "sku": "IPH15PRO128",
          "variant": "128GB Natural Titanium",
          "quantity": 1,
          "price": 129900,
          "image": "https://..."
        }
      ],
      "pricing": {
        "subtotal": 129900,
        "shipping": 0,
        "tax": 23382,
        "discount": 5000,
        "total": 148282
      },
      "payment": {
        "method": "upi",
        "status": "paid",
        "transactionId": "TXN123456",
        "paidAt": 1234567890
      },
      "shipping": {
        "address": {
          "name": "John Doe",
          "phone": "+919876543210",
          "addressLine1": "123 Main St",
          "city": "Mumbai",
          "state": "Maharashtra",
          "pincode": "400001",
          "location": {
            "lat": 19.0760,
            "lng": 72.8777
          }
        },
        "method": "standard",
        "estimatedDelivery": "2024-01-20"
      },
      "status": "confirmed",
      "statusHistory": [
        {
          "status": "pending",
          "timestamp": 1234567890,
          "note": "Order placed"
        },
        {
          "status": "confirmed",
          "timestamp": 1234567900,
          "note": "Payment confirmed",
          "by": "system"
        }
      ],
      "driverId": null,
      "assignedAt": null,
      "pickedUpAt": null,
      "deliveredAt": null,
      "otp": "1234",
      "deliveryProof": {
        "photo": "https://...",
        "signature": "data:image/png;base64,...",
        "timestamp": 1234567890
      },
      "tracking": {
        "updates": [
          {
            "status": "Order confirmed",
            "timestamp": 1234567890,
            "location": {
              "lat": 19.0760,
              "lng": 72.8777
            }
          }
        ]
      },
      "createdAt": 1234567890,
      "updatedAt": 1234567890
    }
  }
}
```

### Drivers Collection

```json
{
  "drivers": {
    "driver123": {
      "driverId": "DRV001",
      "name": "Rajesh Kumar",
      "phone": "+919876543210",
      "email": "rajesh@example.com",
      "profilePhoto": "https://...",
      "vehicle": {
        "type": "bike",
        "number": "MH01AB1234",
        "model": "Honda Activa",
        "color": "Black",
        "photo": "https://..."
      },
      "documents": {
        "drivingLicense": {
          "number": "DL1234567890",
          "photo": "https://...",
          "expiryDate": "2025-12-31",
          "verified": true
        },
        "vehicleRC": {
          "number": "RC1234567890",
          "photo": "https://...",
          "expiryDate": "2025-12-31",
          "verified": true
        },
        "insurance": {
          "policyNumber": "INS1234567890",
          "photo": "https://...",
          "expiryDate": "2025-12-31",
          "verified": true
        },
        "aadhaar": {
          "number": "XXXX-XXXX-1234",
          "photo": "https://...",
          "verified": true
        }
      },
      "bankDetails": {
        "accountHolder": "Rajesh Kumar",
        "accountNumber": "1234567890",
        "ifsc": "SBIN0001234",
        "bankName": "State Bank of India",
        "branch": "Mumbai Main"
      },
      "workingAreas": {
        "zone1": {
          "name": "South Mumbai",
          "pincodes": ["400001", "400002", "400003"]
        }
      },
      "workingHours": {
        "start": "09:00",
        "end": "21:00",
        "breakDuration": 60
      },
      "location": {
        "lat": 19.0760,
        "lng": 72.8777,
        "timestamp": 1234567890,
        "speed": 40,
        "bearing": 90,
        "accuracy": 10
      },
      "status": "online",
      "currentOrder": null,
      "stats": {
        "totalDeliveries": 150,
        "successRate": 98.5,
        "averageRating": 4.7,
        "totalEarnings": 45000
      },
      "earnings": {
        "today": 1500,
        "thisWeek": 8500,
        "thisMonth": 35000,
        "pending": 5000,
        "transactions": {
          "txn1": {
            "type": "delivery_fee",
            "orderId": "order123",
            "amount": 50,
            "timestamp": 1234567890
          }
        }
      },
      "rating": {
        "average": 4.7,
        "count": 120
      },
      "createdAt": 1234567890,
      "lastOnline": 1234567890
    }
  }
}
```

---

## API Integration

### Master API Key

```php
$masterApiKey = 'achal_key-7i3ry8ioio2e3yfuu9ipo7uttrfew';
```

### API Endpoints

#### 1. Customer Activity Tracking

**Endpoint:** `POST /api/customer/activity`

**Headers:**
```
X-API-Key: achal_key-7i3ry8ioio2e3yfuu9ipo7uttrfew
Content-Type: application/json
```

**Request Body:**
```json
{
  "userId": "user123",
  "action": "product_view",
  "data": {
    "productId": "prod123",
    "productName": "iPhone 15 Pro"
  },
  "timestamp": "2024-01-15T10:30:00Z"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Activity logged"
}
```

#### 2. Driver Location Update

**Endpoint:** `POST /api/driver/location`

**Request Body:**
```json
{
  "driverId": "driver123",
  "location": {
    "lat": 19.0760,
    "lng": 72.8777,
    "speed": 40,
    "bearing": 90,
    "accuracy": 10
  },
  "status": "busy",
  "batteryLevel": 85,
  "timestamp": "2024-01-15T10:30:00Z"
}
```

#### 3. Order Assignment

**Endpoint:** `POST /api/admin/assign-order`

**Request Body:**
```json
{
  "orderId": "order123",
  "driverId": "driver123",
  "assignedBy": "admin123",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

#### 4. Push Notification

**Endpoint:** `POST /api/admin/send-notification`

**Request Body:**
```json
{
  "target": "customer",
  "userId": "user123",
  "notification": {
    "title": "Order Confirmed",
    "body": "Your order #ORD001 has been confirmed",
    "icon": "https://...",
    "actionUrl": "/orders/ORD001"
  },
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### PHP Implementation

```php
<?php

class AchalAPI {
    private $apiKey = 'achal_key-7i3ry8ioio2e3yfuu9ipo7uttrfew';
    private $baseUrl = 'https://api.achalmobileshop.com';
    
    public function sendActivity($userId, $action, $data) {
        return $this->makeRequest('/api/customer/activity', [
            'userId' => $userId,
            'action' => $action,
            'data' => $data,
            'timestamp' => date('c')
        ]);
    }
    
    public function updateDriverLocation($driverId, $location, $status) {
        return $this->makeRequest('/api/driver/location', [
            'driverId' => $driverId,
            'location' => $location,
            'status' => $status,
            'timestamp' => date('c')
        ]);
    }
    
    public function assignOrder($orderId, $driverId, $adminId) {
        return $this->makeRequest('/api/admin/assign-order', [
            'orderId' => $orderId,
            'driverId' => $driverId,
            'assignedBy' => $adminId,
            'timestamp' => date('c')
        ]);
    }
    
    public function sendNotification($target, $userId, $notification) {
        return $this->makeRequest('/api/admin/send-notification', [
            'target' => $target,
            'userId' => $userId,
            'notification' => $notification,
            'timestamp' => date('c')
        ]);
    }
    
    private function makeRequest($endpoint, $data) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-API-Key: ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'code' => $httpCode,
            'response' => json_decode($response, true)
        ];
    }
}

// Usage
$api = new AchalAPI();

// Track customer activity
$api->sendActivity('user123', 'product_view', [
    'productId' => 'prod123',
    'productName' => 'iPhone 15 Pro'
]);

// Update driver location
$api->updateDriverLocation('driver123', [
    'lat' => 19.0760,
    'lng' => 72.8777,
    'speed' => 40,
    'bearing' => 90
], 'busy');

// Assign order to driver
$api->assignOrder('order123', 'driver123', 'admin123');

// Send push notification
$api->sendNotification('customer', 'user123', [
    'title' => 'Order Confirmed',
    'body' => 'Your order has been confirmed',
    'actionUrl' => '/orders/ORD001'
]);
?>
```

---

## Deployment

### Step 1: Server Setup

#### Apache Configuration

```apache
<VirtualHost *:443>
    ServerName achalmobileshop.com
    ServerAlias www.achalmobileshop.com
    
    DocumentRoot /var/www/html/achal-mobile-shop
    
    <Directory /var/www/html/achal-mobile-shop>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/achalmobileshop.crt
    SSLCertificateKeyFile /etc/ssl/private/achalmobileshop.key
    
    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Enable Compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
    </IfModule>
    
    # Cache Control
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType image/jpg "access plus 1 year"
        ExpiresByType image/jpeg "access plus 1 year"
        ExpiresByType image/png "access plus 1 year"
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
    </IfModule>
    
    ErrorLog ${APACHE_LOG_DIR}/achal-error.log
    CustomLog ${APACHE_LOG_DIR}/achal-access.log combined
</VirtualHost>
```

#### .htaccess

```apache
# Enable Rewrite Engine
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove www
RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
RewriteRule ^(.*)$ https://%1/$1 [R=301,L]

# Clean URLs
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Security
<FilesMatch "\.(htaccess|htpasswd|ini|log|sh|sql)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Prevent Directory Listing
Options -Indexes

# Protect wp-config.php
<files wp-config.php>
    order allow,deny
    deny from all
</files>
```

### Step 2: SSL Certificate

#### Using Let's Encrypt (Free)

```bash
# Install Certbot
sudo apt-get update
sudo apt-get install certbot python3-certbot-apache

# Obtain Certificate
sudo certbot --apache -d achalmobileshop.com -d www.achalmobileshop.com

# Auto-renewal
sudo certbot renew --dry-run
```

### Step 3: File Upload

```bash
# Using SCP
scp -r achal-mobile-shop/* user@server:/var/www/html/

# Using SFTP
sftp user@server
put -r achal-mobile-shop /var/www/html/

# Using Git
cd /var/www/html
git clone https://github.com/yourusername/achal-mobile-shop.git
```

### Step 4: Set Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/html/achal-mobile-shop

# Set permissions
sudo find /var/www/html/achal-mobile-shop -type d -exec chmod 755 {} \;
sudo find /var/www/html/achal-mobile-shop -type f -exec chmod 644 {} \;
```

### Step 5: Environment Variables

Create `.env` file:

```env
# Firebase
FIREBASE_API_KEY=AIzaSyBbTaQvWY9Z1DfXI8SGXQlfzplPFe3TLPg
FIREBASE_AUTH_DOMAIN=mobile-shop-e8bd6.firebaseapp.com
FIREBASE_DATABASE_URL=https://mobile-shop-e8bd6-default-rtdb.firebaseio.com
FIREBASE_PROJECT_ID=mobile-shop-e8bd6

# Google Maps
GOOGLE_MAPS_API_KEY=AIzaSyC5JfXiY2lPvQ7jmJXVk-ODZT98MRBYJVg

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=465
SMTP_USERNAME=pranshurathaor285@gmail.com
SMTP_PASSWORD=btfnvjkjhbozihbo

# API
MASTER_API_KEY=achal_key-7i3ry8ioio2e3yfuu9ipo7uttrfew

# App
APP_ENV=production
APP_DEBUG=false
APP_URL=https://achalmobileshop.com
```

---

## Testing

### Unit Testing

```php
<?php
// tests/OTPTest.php

use PHPUnit\Framework\TestCase;

class OTPTest extends TestCase {
    public function testGenerateOTP() {
        $otp = generateOTP();
        $this->assertEquals(6, strlen($otp));
        $this->assertIsNumeric($otp);
    }
    
    public function testOTPExpiry() {
        $_SESSION['otp_time'] = time() - 700; // 11 minutes ago
        $this->assertTrue(isOTPExpired());
        
        $_SESSION['otp_time'] = time() - 300; // 5 minutes ago
        $this->assertFalse(isOTPExpired());
    }
}
?>
```

### Integration Testing

```javascript
// tests/firebase.test.js

describe('Firebase Integration', () => {
    test('User can register', async () => {
        const email = 'test@example.com';
        const password = 'Test123!';
        
        const userCredential = await firebase.auth()
            .createUserWithEmailAndPassword(email, password);
        
        expect(userCredential.user).toBeDefined();
        expect(userCredential.user.email).toBe(email);
    });
    
    test('User data is saved to database', async () => {
        const userId = 'test123';
        const userData = {
            name: 'Test User',
            email: 'test@example.com'
        };
        
        await firebase.database()
            .ref(`users/${userId}`)
            .set(userData);
        
        const snapshot = await firebase.database()
            .ref(`users/${userId}`)
            .once('value');
        
        expect(snapshot.val()).toEqual(userData);
    });
});
```

### End-to-End Testing

```javascript
// tests/e2e/customer-flow.test.js

describe('Customer Purchase Flow', () => {
    test('Complete purchase journey', async () => {
        // 1. Register
        await page.goto('https://achalmobileshop.com');
        await page.click('#registerTab');
        await page.fill('#email', 'test@example.com');
        await page.fill('#password', 'Test123!');
        await page.click('#registerBtn');
        
        // 2. Verify OTP
        const otp = await getOTPFromEmail('test@example.com');
        await page.fill('#otpInput', otp);
        await page.click('#verifyBtn');
        
        // 3. Browse products
        await page.click('#productsTab');
        await page.click('.product-card:first-child');
        
        // 4. Add to cart
        await page.click('#addToCartBtn');
        
        // 5. Checkout
        await page.click('#cartIcon');
        await page.click('#checkoutBtn');
        
        // 6. Enter address
        await page.fill('#address', '123 Main St');
        await page.click('#continueBtn');
        
        // 7. Select payment
        await page.click('#codPayment');
        await page.click('#placeOrderBtn');
        
        // 8. Verify order placed
        await page.waitForSelector('.order-success');
        expect(await page.textContent('.order-id')).toMatch(/ORD\d+/);
    });
});
```

---

## Performance Optimization

### 1. Image Optimization

```php
<?php
function optimizeImage($source, $destination, $quality = 80) {
    $info = getimagesize($source);
    
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        return false;
    }
    
    imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    
    return true;
}
?>
```

### 2. Caching Strategy

```php
<?php
// Cache products for 1 hour
function getCachedProducts() {
    $cacheFile = 'cache/products.json';
    $cacheTime = 3600; // 1 hour
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    
    // Fetch from Firebase
    $products = fetchProductsFromFirebase();
    
    // Save to cache
    file_put_contents($cacheFile, json_encode($products));
    
    return $products;
}
?>
```

### 3. Database Indexing

```json
{
  "rules": {
    "products": {
      ".indexOn": ["category", "brand", "price", "createdAt"]
    },
    "orders": {
      ".indexOn": ["userId", "status", "createdAt"]
    },
    "drivers": {
      ".indexOn": ["status", "location"]
    }
  }
}
```

---

## Security Best Practices

### 1. Input Validation

```php
<?php
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
?>
```

### 2. CSRF Protection

```php
<?php
session_start();

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
```

### 3. Rate Limiting

```php
<?php
function checkRateLimit($identifier, $limit = 5, $period = 60) {
    $key = "rate_limit_$identifier";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 1,
            'start_time' => time()
        ];
        return true;
    }
    
    $elapsed = time() - $_SESSION[$key]['start_time'];
    
    if ($elapsed > $period) {
        $_SESSION[$key] = [
            'count' => 1,
            'start_time' => time()
        ];
        return true;
    }
    
    if ($_SESSION[$key]['count'] >= $limit) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}
?>
```

---

## Monitoring & Analytics

### 1. Error Logging

```php
<?php
function logError($message, $context = []) {
    $logFile = 'logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = json_encode($context);
    
    $logMessage = "[$timestamp] $message | Context: $contextStr\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Also send to Firebase for admin monitoring
    firebase_log_error($message, $context);
}
?>
```

### 2. Performance Monitoring

```javascript
// Track page load time
window.addEventListener('load', () => {
    const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
    
    firebase.database().ref('analytics/performance').push({
        page: window.location.pathname,
        loadTime: loadTime,
        timestamp: Date.now()
    });
});
```

### 3. User Analytics

```javascript
// Track user actions
function trackEvent(category, action, label) {
    firebase.database().ref('analytics/events').push({
        category: category,
        action: action,
        label: label,
        userId: firebase.auth().currentUser?.uid,
        timestamp: Date.now()
    });
}

// Usage
trackEvent('Product', 'View', 'iPhone 15 Pro');
trackEvent('Cart', 'Add', 'iPhone 15 Pro');
trackEvent('Order', 'Place', 'ORD001');
```

---

## Troubleshooting

### Common Issues

#### 1. Firebase Connection Failed

**Problem:** Can't connect to Firebase

**Solutions:**
- Verify API keys are correct
- Check Firebase project is active
- Ensure domain is whitelisted in Firebase console
- Check browser console for CORS errors

#### 2. OTP Not Sending

**Problem:** Email OTP not received

**Solutions:**
- Verify SMTP credentials
- Check spam folder
- Enable "Less secure app access" in Gmail
- Use App Password instead of regular password
- Check PHP mail() function is enabled

#### 3. Google Maps Not Loading

**Problem:** Map shows gray screen

**Solutions:**
- Verify API key is correct
- Check API key restrictions
- Ensure billing is enabled
- Check browser console for errors
- Verify Maps JavaScript API is enabled

#### 4. PWA Not Installing

**Problem:** Install prompt not showing

**Solutions:**
- Ensure HTTPS is enabled
- Verify manifest.json is valid
- Check Service Worker is registered
- Test on supported browser (Chrome, Edge, Safari)
- Clear browser cache

---

## Support & Resources

### Documentation
- [Firebase Docs](https://firebase.google.com/docs)
- [Google Maps API](https://developers.google.com/maps/documentation)
- [PHP Manual](https://www.php.net/manual/en/)
- [MDN Web Docs](https://developer.mozilla.org/)

### Community
- [Stack Overflow](https://stackoverflow.com/)
- [Firebase Community](https://firebase.google.com/community)
- [GitHub Issues](https://github.com/yourusername/achal-mobile-shop/issues)

### Contact
- Email: support@achalmobileshop.com
- Phone: +91 XXXXX XXXXX
- Website: https://achalmobileshop.com

---

**Last Updated:** January 2024  
**Version:** 1.0  
**Author:** Achal Mobile Shop Development Team
