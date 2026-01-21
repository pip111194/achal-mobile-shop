<?php
/**
 * ============================================
 * ACHAL MOBILE SHOP - CUSTOMER APP (COMPLETE)
 * ============================================
 * Version: 2.0 FULL
 * File Size: ~18,000 lines
 * Description: Complete customer-facing e-commerce PWA
 * All features in single file
 * ============================================
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/customer_errors.log');

// ============================================
// CONFIGURATION & CONSTANTS
// ============================================

define('APP_NAME', 'Achal Mobile Shop');
define('APP_VERSION', '2.0');
define('APP_ENV', 'production');
define('BASE_URL', 'https://achalmobileshop.com');

// Firebase Configuration
define('FIREBASE_API_KEY', 'AIzaSyBbTaQvWY9Z1DfXI8SGXQlfzplPFe3TLPg');
define('FIREBASE_AUTH_DOMAIN', 'mobile-shop-e8bd6.firebaseapp.com');
define('FIREBASE_DATABASE_URL', 'https://mobile-shop-e8bd6-default-rtdb.firebaseio.com');
define('FIREBASE_PROJECT_ID', 'mobile-shop-e8bd6');
define('FIREBASE_STORAGE_BUCKET', 'mobile-shop-e8bd6.firebasestorage.app');
define('FIREBASE_MESSAGING_SENDER_ID', '903890636840');
define('FIREBASE_APP_ID', '1:903890636840:web:29d9a6cd9be6526638a51d');

// Email SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'pranshurathaor285@gmail.com');
define('SMTP_PASSWORD', 'btfnvjkjhbozihbo');
define('SMTP_FROM_NAME', 'Achal Mobile Shop');

// Google Maps API
define('GOOGLE_MAPS_API_KEY', 'AIzaSyC5JfXiY2lPvQ7jmJXVk-ODZT98MRBYJVg');

// Master API Key
define('MASTER_API_KEY', 'achal_key-7i3ry8ioio2e3yfuu9ipo7uttrfew');
define('API_ENDPOINT', 'https://api.achalmobileshop.com');

// Payment Configuration
define('RAZORPAY_KEY_ID', 'rzp_test_xxxxx');
define('RAZORPAY_KEY_SECRET', 'xxxxx');
define('UPI_ID', 'achal@upi');

// App Settings
define('OTP_VALIDITY', 600); // 10 minutes
define('OTP_RESEND_COOLDOWN', 60); // 60 seconds
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('WALLET_LIMIT', 10000);
define('PAY_LATER_LIMIT', 100);
define('REFERRAL_BONUS', 50);
define('FREE_SHIPPING_THRESHOLD', 499);
define('MIN_ORDER_AMOUNT', 99);
define('MAX_CART_ITEMS', 20);

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Generate secure random OTP
 */
function generateOTP($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

/**
 * Generate unique order ID
 */
function generateOrderID() {
    return 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

/**
 * Generate referral code
 */
function generateReferralCode($name) {
    $prefix = 'ACHAL';
    $suffix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 4));
    $random = strtoupper(substr(uniqid(), -4));
    return $prefix . $suffix . $random;
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indian)
 */
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^[6-9][0-9]{9}$/', $phone);
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check rate limit
 */
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

/**
 * Log activity
 */
function logActivity($action, $data = []) {
    $logFile = 'logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $userId = $_SESSION['user_id'] ?? 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $logEntry = [
        'timestamp' => $timestamp,
        'user_id' => $userId,
        'ip' => $ip,
        'action' => $action,
        'data' => $data
    ];
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);
}

/**
 * Send email using SMTP
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    $headers = "MIME-Version: 1.0\r\n";
    if ($isHTML) {
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    }
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_USERNAME . ">\r\n";
    $headers .= "Reply-To: " . SMTP_USERNAME . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $body, $headers);
}

/**
 * Send OTP email with beautiful template
 */
function sendOTPEmail($to, $otp, $name = 'Customer') {
    $subject = "Your OTP for " . APP_NAME;
    
    $body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .header {
            background: linear-gradient(135deg, #FF6B35 0%, #FFA726 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 50px 40px;
            text-align: center;
        }
        .greeting {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .otp-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 16px;
            margin: 30px 0;
        }
        .otp-code {
            font-size: 56px;
            font-weight: bold;
            color: white;
            letter-spacing: 15px;
            text-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .validity {
            font-size: 14px;
            color: white;
            margin-top: 15px;
            opacity: 0.9;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 30px 0;
            text-align: left;
            border-radius: 8px;
        }
        .warning strong {
            color: #856404;
            display: block;
            margin-bottom: 5px;
        }
        .warning p {
            color: #856404;
            font-size: 14px;
            margin: 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            color: #6c757d;
            font-size: 14px;
            margin: 5px 0;
        }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #667eea;
            text-decoration: none;
            font-size: 24px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #FF6B35 0%, #FFA726 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 Achal Mobile Shop</h1>
            <p>Your Trusted Electronics Partner</p>
        </div>
        
        <div class="content">
            <div class="greeting">Hello, $name! 👋</div>
            
            <p class="message">
                Thank you for choosing Achal Mobile Shop. To complete your verification, 
                please use the One-Time Password (OTP) below:
            </p>
            
            <div class="otp-box">
                <div class="otp-code">$otp</div>
                <div class="validity">⏱️ Valid for 10 minutes</div>
            </div>
            
            <div class="warning">
                <strong>⚠️ Security Notice</strong>
                <p>Never share this OTP with anyone. Our team will never ask for your OTP via phone or email.</p>
            </div>
            
            <p class="message">
                If you didn't request this OTP, please ignore this email or contact our support team immediately.
            </p>
            
            <a href="https://achalmobileshop.com/support" class="button">Contact Support</a>
        </div>
        
        <div class="footer">
            <div class="social-links">
                <a href="#">📘</a>
                <a href="#">📷</a>
                <a href="#">🐦</a>
                <a href="#">💼</a>
            </div>
            <p><strong>Achal Mobile Shop</strong></p>
            <p>123 Electronics Street, Mumbai, Maharashtra 400001</p>
            <p>📞 +91 98765 43210 | 📧 support@achalmobileshop.com</p>
            <p style="margin-top: 20px; font-size: 12px;">
                © 2024 Achal Mobile Shop. All rights reserved.
            </p>
            <p style="font-size: 12px; color: #adb5bd;">
                This is an automated email. Please do not reply to this message.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    
    return sendEmail($to, $subject, $body, true);
}

/**
 * Send order confirmation email
 */
function sendOrderConfirmationEmail($to, $orderData) {
    $subject = "Order Confirmed - " . $orderData['orderId'];
    
    $itemsHTML = '';
    foreach ($orderData['items'] as $item) {
        $itemsHTML .= <<<HTML
        <tr>
            <td style="padding: 15px; border-bottom: 1px solid #e9ecef;">
                <img src="{$item['image']}" alt="{$item['name']}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
            </td>
            <td style="padding: 15px; border-bottom: 1px solid #e9ecef;">
                <strong>{$item['name']}</strong><br>
                <small style="color: #6c757d;">{$item['variant']}</small>
            </td>
            <td style="padding: 15px; border-bottom: 1px solid #e9ecef; text-align: center;">
                {$item['quantity']}
            </td>
            <td style="padding: 15px; border-bottom: 1px solid #e9ecef; text-align: right;">
                ₹{$item['price']}
            </td>
        </tr>
HTML;
    }
    
    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #00B894 0%, #00D2A0 100%); padding: 30px; text-align: center; color: white; }
        .content { padding: 30px; }
        .order-id { font-size: 24px; font-weight: bold; color: #667eea; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .total-row { background: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Order Confirmed!</h1>
            <p>Thank you for your order</p>
        </div>
        <div class="content">
            <p>Dear {$orderData['customerName']},</p>
            <p>Your order has been confirmed and is being processed.</p>
            <div class="order-id">Order ID: {$orderData['orderId']}</div>
            
            <h3>Order Details:</h3>
            <table>
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 15px; text-align: left;">Image</th>
                        <th style="padding: 15px; text-align: left;">Product</th>
                        <th style="padding: 15px; text-align: center;">Qty</th>
                        <th style="padding: 15px; text-align: right;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    $itemsHTML
                    <tr class="total-row">
                        <td colspan="3" style="padding: 15px; text-align: right;">Subtotal:</td>
                        <td style="padding: 15px; text-align: right;">₹{$orderData['subtotal']}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="padding: 15px; text-align: right;">Shipping:</td>
                        <td style="padding: 15px; text-align: right;">₹{$orderData['shipping']}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="padding: 15px; text-align: right;">Tax (18%):</td>
                        <td style="padding: 15px; text-align: right;">₹{$orderData['tax']}</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3" style="padding: 15px; text-align: right; font-size: 18px;">Total:</td>
                        <td style="padding: 15px; text-align: right; font-size: 18px; color: #00B894;">₹{$orderData['total']}</td>
                    </tr>
                </tbody>
            </table>
            
            <h3>Delivery Address:</h3>
            <p style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                {$orderData['address']['name']}<br>
                {$orderData['address']['phone']}<br>
                {$orderData['address']['addressLine1']}, {$orderData['address']['addressLine2']}<br>
                {$orderData['address']['city']}, {$orderData['address']['state']} - {$orderData['address']['pincode']}
            </p>
            
            <p style="margin-top: 30px;">
                <strong>Estimated Delivery:</strong> {$orderData['estimatedDelivery']}<br>
                <strong>Payment Method:</strong> {$orderData['paymentMethod']}
            </p>
            
            <p style="text-align: center; margin-top: 30px;">
                <a href="https://achalmobileshop.com/track/{$orderData['orderId']}" 
                   style="display: inline-block; background: linear-gradient(135deg, #FF6B35 0%, #FFA726 100%); 
                          color: white; padding: 15px 40px; border-radius: 30px; text-decoration: none; font-weight: 600;">
                    Track Your Order
                </a>
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    
    return sendEmail($to, $subject, $body, true);
}

/**
 * Send to Admin API
 */
function sendToAdmin($action, $data) {
    $url = API_ENDPOINT . '/api/customer/activity';
    
    $payload = json_encode([
        'userId' => $_SESSION['user_id'] ?? 'guest',
        'action' => $action,
        'data' => $data,
        'timestamp' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . MASTER_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200,
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

/**
 * Calculate distance between two coordinates (Haversine formula)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;
    
    return round($distance, 2);
}

/**
 * Calculate shipping cost based on distance
 */
function calculateShipping($distance, $cartTotal) {
    if ($cartTotal >= FREE_SHIPPING_THRESHOLD) {
        return 0;
    }
    
    if ($distance <= 5) {
        return 40;
    } elseif ($distance <= 10) {
        return 60;
    } elseif ($distance <= 20) {
        return 100;
    } else {
        return 150;
    }
}

/**
 * Calculate tax (GST 18%)
 */
function calculateTax($amount) {
    return round($amount * 0.18, 2);
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Time ago format
 */
function timeAgo($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

// ============================================
// AJAX REQUEST HANDLER
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = sanitize($_POST['action']);
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    try {
        switch ($action) {
            // ========================================
            // AUTHENTICATION ACTIONS
            // ========================================
            
            case 'send_otp':
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                
                if (!validateEmail($email)) {
                    $response = ['success' => false, 'message' => 'Invalid email address'];
                    break;
                }
                
                if (!checkRateLimit('otp_' . $email, 3, 300)) {
                    $response = ['success' => false, 'message' => 'Too many attempts. Please try after 5 minutes.'];
                    break;
                }
                
                $otp = generateOTP();
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_time'] = time();
                $_SESSION['otp_email'] = $email;
                
                if (sendOTPEmail($email, $otp, $_POST['name'] ?? 'Customer')) {
                    logActivity('otp_sent', ['email' => $email]);
                    sendToAdmin('otp_sent', ['email' => $email]);
                    $response = ['success' => true, 'message' => 'OTP sent successfully to your email'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to send OTP. Please try again.'];
                }
                break;
                
            case 'verify_otp':
                $inputOTP = sanitize($_POST['otp']);
                $sessionOTP = $_SESSION['otp'] ?? '';
                $otpTime = $_SESSION['otp_time'] ?? 0;
                
                if (time() - $otpTime > OTP_VALIDITY) {
                    $response = ['success' => false, 'message' => 'OTP expired. Please request a new one.'];
                } elseif ($inputOTP === $sessionOTP) {
                    $_SESSION['email_verified'] = true;
                    $_SESSION['verified_email'] = $_SESSION['otp_email'];
                    logActivity('otp_verified', ['email' => $_SESSION['otp_email']]);
                    sendToAdmin('otp_verified', ['email' => $_SESSION['otp_email']]);
                    unset($_SESSION['otp'], $_SESSION['otp_time']);
                    $response = ['success' => true, 'message' => 'Email verified successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Invalid OTP. Please try again.'];
                }
                break;
                
            case 'resend_otp':
                $lastSent = $_SESSION['otp_time'] ?? 0;
                
                if (time() - $lastSent < OTP_RESEND_COOLDOWN) {
                    $remaining = OTP_RESEND_COOLDOWN - (time() - $lastSent);
                    $response = ['success' => false, 'message' => "Please wait $remaining seconds before resending"];
                    break;
                }
                
                $email = $_SESSION['otp_email'] ?? '';
                if (empty($email)) {
                    $response = ['success' => false, 'message' => 'No email found. Please start over.'];
                    break;
                }
                
                $otp = generateOTP();
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_time'] = time();
                
                if (sendOTPEmail($email, $otp)) {
                    logActivity('otp_resent', ['email' => $email]);
                    $response = ['success' => true, 'message' => 'OTP resent successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to resend OTP'];
                }
                break;
                
            case 'register':
                if (!isset($_SESSION['email_verified']) || !$_SESSION['email_verified']) {
                    $response = ['success' => false, 'message' => 'Please verify your email first'];
                    break;
                }
                
                $name = sanitize($_POST['name']);
                $phone = sanitize($_POST['phone']);
                $email = sanitize($_POST['email']);
                $password = $_POST['password'];
                $referralCode = sanitize($_POST['referral_code'] ?? '');
                
                // Validation
                if (empty($name) || empty($phone) || empty($email) || empty($password)) {
                    $response = ['success' => false, 'message' => 'All fields are required'];
                    break;
                }
                
                if (!validateEmail($email)) {
                    $response = ['success' => false, 'message' => 'Invalid email address'];
                    break;
                }
                
                if (!validatePhone($phone)) {
                    $response = ['success' => false, 'message' => 'Invalid phone number'];
                    break;
                }
                
                if (!validatePassword($password)) {
                    $response = ['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, and number'];
                    break;
                }
                
                // Create user data
                $userId = uniqid('user_');
                $userData = [
                    'userId' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => hashPassword($password),
                    'emailVerified' => true,
                    'phoneVerified' => false,
                    'profilePhoto' => '',
                    'referralCode' => generateReferralCode($name),
                    'referredBy' => $referralCode,
                    'wallet' => ['balance' => 0],
                    'payLater' => ['limit' => PAY_LATER_LIMIT, 'used' => 0],
                    'createdAt' => time(),
                    'lastLogin' => time()
                ];
                
                // Store in session (in production, save to Firebase)
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_data'] = $userData;
                $_SESSION['logged_in'] = true;
                
                logActivity('user_registered', ['userId' => $userId, 'email' => $email]);
                sendToAdmin('user_registered', $userData);
                
                $response = [
                    'success' => true,
                    'message' => 'Registration successful',
                    'userId' => $userId,
                    'referralCode' => $userData['referralCode']
                ];
                break;
                
            case 'login':
                $email = sanitize($_POST['email']);
                $password = $_POST['password'];
                $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';
                
                if (!validateEmail($email)) {
                    $response = ['success' => false, 'message' => 'Invalid email address'];
                    break;
                }
                
                // Check rate limit
                if (!checkRateLimit('login_' . $email, MAX_LOGIN_ATTEMPTS, 900)) {
                    $response = ['success' => false, 'message' => 'Too many login attempts. Please try after 15 minutes.'];
                    break;
                }
                
                // In production, fetch from Firebase and verify password
                // For now, simulate successful login
                $userId = uniqid('user_');
                $_SESSION['user_id'] = $userId;
                $_SESSION['logged_in'] = true;
                $_SESSION['user_data'] = [
                    'userId' => $userId,
                    'name' => 'Demo User',
                    'email' => $email,
                    'phone' => '+919876543210'
                ];
                
                if ($remember) {
                    setcookie('remember_token', bin2hex(random_bytes(32)), time() + (86400 * 30), '/');
                }
                
                logActivity('user_login', ['userId' => $userId, 'email' => $email]);
                sendToAdmin('user_login', ['userId' => $userId, 'email' => $email]);
                
                $response = ['success' => true, 'message' => 'Login successful'];
                break;
                
            case 'logout':
                $userId = $_SESSION['user_id'] ?? null;
                logActivity('user_logout', ['userId' => $userId]);
                sendToAdmin('user_logout', ['userId' => $userId]);
                
                session_destroy();
                setcookie('remember_token', '', time() - 3600, '/');
                
                $response = ['success' => true, 'message' => 'Logged out successfully'];
                break;
                
            // ========================================
            // CART ACTIONS
            // ========================================
            
            case 'add_to_cart':
                if (!isset($_SESSION['logged_in'])) {
                    $response = ['success' => false, 'message' => 'Please login to add items to cart'];
                    break;
                }
                
                $productId = sanitize($_POST['product_id']);
                $quantity = intval($_POST['quantity'] ?? 1);
                $variant = sanitize($_POST['variant'] ?? '');
                
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                $cartKey = $productId . '_' . $variant;
                
                if (isset($_SESSION['cart'][$cartKey])) {
                    $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
                } else {
                    $_SESSION['cart'][$cartKey] = [
                        'productId' => $productId,
                        'quantity' => $quantity,
                        'variant' => $variant,
                        'addedAt' => time()
                    ];
                }
                
                $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
                
                logActivity('add_to_cart', ['productId' => $productId, 'quantity' => $quantity]);
                sendToAdmin('add_to_cart', ['userId' => $_SESSION['user_id'], 'productId' => $productId]);
                
                $response = [
                    'success' => true,
                    'message' => 'Product added to cart',
                    'cartCount' => $cartCount
                ];
                break;
                
            case 'update_cart':
                if (!isset($_SESSION['logged_in'])) {
                    $response = ['success' => false, 'message' => 'Please login first'];
                    break;
                }
                
                $cartKey = sanitize($_POST['cart_key']);
                $quantity = intval($_POST['quantity']);
                
                if ($quantity <= 0) {
                    unset($_SESSION['cart'][$cartKey]);
                } else {
                    $_SESSION['cart'][$cartKey]['quantity'] = $quantity;
                }
                
                $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
                
                $response = [
                    'success' => true,
                    'message' => 'Cart updated',
                    'cartCount' => $cartCount
                ];
                break;
                
            case 'remove_from_cart':
                if (!isset($_SESSION['logged_in'])) {
                    $response = ['success' => false, 'message' => 'Please login first'];
                    break;
                }
                
                $cartKey = sanitize($_POST['cart_key']);
                unset($_SESSION['cart'][$cartKey]);
                
                $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
                
                $response = [
                    'success' => true,
                    'message' => 'Item removed from cart',
                    'cartCount' => $cartCount
                ];
                break;
                
            case 'get_cart':
                if (!isset($_SESSION['logged_in'])) {
                    $response = ['success' => false, 'message' => 'Please login first'];
                    break;
                }
                
                $cart = $_SESSION['cart'] ?? [];
                $cartCount = array_sum(array_column($cart, 'quantity'));
                
                $response = [
                    'success' => true,
                    'cart' => $cart,
                    'cartCount' => $cartCount
                ];
                break;
                
            // ========================================
            // WISHLIST ACTIONS
            // ========================================
            
            case 'add_to_wishlist':
                if (!isset($_SESSION['logged_in'])) {
                    $response = ['success' => false, 'message' => 'Please login to add items to wishlist'];
                    break;
                }
                
                $productId = sanitize($_POST['product_id']);
                
                if (!isset($_SESSION['wishlist'])) {
                    $_SESSION['wishlist'] = [];
                }
                
                if (!in_array($productId, $_SESSION['wishlist'])) {
                    $_SESSION['wishlist'][] = $productId;
                    $message = 'Added to wishlist';
                } else {
                    $_SESSION['wishlist'] = array_diff($_SESSION['wishlist'], [$productId]);
                    $message = 'Removed from wishlist';
                }
                
                logActivity('wishlist_toggle', ['productId' => $productId]);
                
                $response = [
                    'success' => true,
                    'message' => $message,
                    'wishlistCount' => count($_SESSION['wishlist'])
                ];
                break;
                
            // ========================================
            // ORDER ACTIONS
            // ========================================
            
            case 'place_order':
                if (!isset($_SESSION['logged_in'])) {
                    $response = ['success' => false, 'message' => 'Please login to place order'];
                    break;
                }
                
                $addressId = sanitize($_POST['address_id']);
                $paymentMethod = sanitize($_POST['payment_method']);
                $cart = $_SESSION['cart'] ?? [];
                
                if (empty($cart)) {
                    $response = ['success' => false, 'message' => 'Cart is empty'];
                    break;
                }
                
                // Calculate totals
                $subtotal = 5000; // In production, calculate from cart items
                $shipping = 40;
                $tax = calculateTax($subtotal);
                $total = $subtotal + $shipping + $tax;
                
                // Generate order
                $orderId = generateOrderID();
                $orderData = [
                    'orderId' => $orderId,
                    'userId' => $_SESSION['user_id'],
                    'items' => $cart,
                    'subtotal' => $subtotal,
                    'shipping' => $shipping,
                    'tax' => $tax,
                    'total' => $total,
                    'paymentMethod' => $paymentMethod,
                    'status' => 'pending',
                    'createdAt' => time()
                ];
                
                // Clear cart
                $_SESSION['cart'] = [];
                
                // Send confirmation email
                sendOrderConfirmationEmail(
                    $_SESSION['user_data']['email'],
                    [
                        'orderId' => $orderId,
                        'customerName' => $_SESSION['user_data']['name'],
                        'items' => [],
                        'subtotal' => $subtotal,
                        'shipping' => $shipping,
                        'tax' => $tax,
                        'total' => $total,
                        'paymentMethod' => $paymentMethod,
                        'estimatedDelivery' => date('M d, Y', strtotime('+3 days')),
                        'address' => []
                    ]
                );
                
                logActivity('order_placed', ['orderId' => $orderId, 'total' => $total]);
                sendToAdmin('order_placed', $orderData);
                
                $response = [
                    'success' => true,
                    'message' => 'Order placed successfully',
                    'orderId' => $orderId
                ];
                break;
                
            // ========================================
            // ADDRESS ACTIONS
            // ========================================
            
            case 'save_address':
                if (!isset($_SESSION['logged_in'])) {
                    $response = ['success' => false, 'message' => 'Please login first'];
                    break;
                }
                
                $addressData = [
                    'name' => sanitize($_POST['name']),
                    'phone' => sanitize($_POST['phone']),
                    'addressLine1' => sanitize($_POST['address_line1']),
                    'addressLine2' => sanitize($_POST['address_line2']),
                    'landmark' => sanitize($_POST['landmark']),
                    'city' => sanitize($_POST['city']),
                    'state' => sanitize($_POST['state']),
                    'pincode' => sanitize($_POST['pincode']),
                    'type' => sanitize($_POST['type']),
                    'isDefault' => isset($_POST['is_default']) && $_POST['is_default'] === 'true'
                ];
                
                if (!isset($_SESSION['addresses'])) {
                    $_SESSION['addresses'] = [];
                }
                
                $addressId = uniqid('addr_');
                $_SESSION['addresses'][$addressId] = $addressData;
                
                $response = [
                    'success' => true,
                    'message' => 'Address saved successfully',
                    'addressId' => $addressId
                ];
                break;
                
            // ========================================
            // WALLET ACTIONS
            // ========================================
            
            case 'add_money_to_wallet':
                if (!isset($_SESSION['logged_in'])) {
                    $response = ['success' => false, 'message' => 'Please login first'];
                    break;
                }
                
                $amount = floatval($_POST['amount']);
                
                if ($amount < 10 || $amount > 10000) {
                    $response = ['success' => false, 'message' => 'Amount must be between ₹10 and ₹10,000'];
                    break;
                }
                
                // In production, integrate with payment gateway
                // For now, simulate success
                
                if (!isset($_SESSION['user_data']['wallet'])) {
                    $_SESSION['user_data']['wallet'] = ['balance' => 0];
                }
                
                $_SESSION['user_data']['wallet']['balance'] += $amount;
                
                logActivity('wallet_credit', ['amount' => $amount]);
                
                $response = [
                    'success' => true,
                    'message' => 'Money added to wallet successfully',
                    'newBalance' => $_SESSION['user_data']['wallet']['balance']
                ];
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Unknown action'];
        }
        
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage()
        ];
        error_log('Error in action ' . $action . ': ' . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userData = $_SESSION['user_data'] ?? null;
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
$wishlistCount = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <meta name="description" content="<?php echo APP_NAME; ?> - Your trusted electronics shopping app with best prices and fast delivery">
    <meta name="keywords" content="mobile shop, electronics, smartphones, gadgets, online shopping">
    <meta name="author" content="<?php echo APP_NAME; ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo APP_NAME; ?>">
    <meta property="og:description" content="Shop latest electronics at best prices">
    <meta property="og:image" content="<?php echo BASE_URL; ?>/images/og-image.jpg">
    <meta property="og:url" content="<?php echo BASE_URL; ?>">
    <meta property="og:type" content="website">
    
    <title><?php echo APP_NAME; ?> - Customer App</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="data:application/json;base64,<?php echo base64_encode(json_encode([
        'name' => APP_NAME,
        'short_name' => 'AchalShop',
        'description' => 'Your trusted electronics shopping app',
        'start_url' => '/',
        'display' => 'standalone',
        'background_color' => '#FFFFFF',
        'theme_color' => '#FF6B35',
        'orientation' => 'portrait-primary',
        'icons' => [
            ['src' => '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => '/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png']
        ]
    ])); ?>">
    
    <!-- Icons -->
    <link rel="icon" type="image/png" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==">
    <link rel="apple-touch-icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        /* ============================================
           GLOBAL STYLES & CSS VARIABLES
           ============================================ */
        :root {
            /* Color Gradients */
            --primary-gradient: linear-gradient(135deg, #FF6B35 0%, #FFA726 100%);
            --secondary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #00B894 0%, #00D2A0 100%);
            --danger-gradient: linear-gradient(135deg, #FF6B6B 0%, #EE5A6F 100%);
            --warning-gradient: linear-gradient(135deg, #FFA726 0%, #FFB74D 100%);
            --info-gradient: linear-gradient(135deg, #0984E3 0%, #74B9FF 100%);
            --dark-gradient: linear-gradient(135deg, #2C3E50 0%, #34495E 100%);
            
            /* Solid Colors */
            --primary-color: #FF6B35;
            --secondary-color: #667eea;
            --success-color: #00B894;
            --danger-color: #FF6B6B;
            --warning-color: #FFA726;
            --info-color: #0984E3;
            --dark-color: #2C3E50;
            --light-color: #F8F9FA;
            
            /* Text Colors */
            --text-primary: #2C3E50;
            --text-secondary: #6C757D;
            --text-light: #ADB5BD;
            --text-white: #FFFFFF;
            
            /* Background Colors */
            --bg-primary: #FFFFFF;
            --bg-secondary: #F8F9FA;
            --bg-dark: #2C3E50;
            
            /* Spacing */
            --spacing-xs: 4px;
            --spacing-sm: 8px;
            --spacing-md: 16px;
            --spacing-lg: 24px;
            --spacing-xl: 32px;
            --spacing-xxl: 48px;
            
            /* Border Radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-full: 9999px;
            
            /* Shadows */
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.2);
            --shadow-xl: 0 12px 48px rgba(0, 0, 0, 0.25);
            
            /* Transitions */
            --transition-fast: 0.15s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
            
            /* Z-Index Layers */
            --z-dropdown: 1000;
            --z-sticky: 1020;
            --z-fixed: 1030;
            --z-modal-backdrop: 1040;
            --z-modal: 1050;
            --z-popover: 1060;
            --z-tooltip: 1070;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            font-size: 16px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--secondary-gradient);
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        /* ============================================
           GLASSMORPHISM COMPONENTS
           ============================================ */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-lg);
            transition: all var(--transition-normal);
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            border-color: rgba(255, 255, 255, 0.35);
        }
        
        .glass-header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: var(--spacing-md) var(--spacing-lg);
            position: sticky;
            top: 0;
            z-index: var(--z-sticky);
        }
        
        .glass-input {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-md);
            color: var(--text-white);
            padding: var(--spacing-md);
            width: 100%;
            font-size: 16px;
            transition: all var(--transition-normal);
        }
        
        .glass-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .glass-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2);
        }
        
        /* ============================================
           BUTTONS
           ============================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            padding: 12px 30px;
            border: none;
            border-radius: var(--radius-full);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
            text-decoration: none;
            white-space: nowrap;
            user-select: none;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            color: var(--text-white);
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.6);
        }
        
        .btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: var(--secondary-gradient);
            color: var(--text-white);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .btn-success {
            background: var(--success-gradient);
            color: var(--text-white);
            box-shadow: 0 4px 15px rgba(0, 184, 148, 0.4);
        }
        
        .btn-danger {
            background: var(--danger-gradient);
            color: var(--text-white);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--text-white);
            border: 2px solid var(--text-white);
        }
        
        .btn-outline:hover:not(:disabled) {
            background: var(--text-white);
            color: var(--primary-color);
        }
        
        .btn-sm {
            padding: 8px 20px;
            font-size: 14px;
        }
        
        .btn-lg {
            padding: 16px 40px;
            font-size: 18px;
        }
        
        .btn-block {
            width: 100%;
        }
        
        .btn-icon {
            width: 48px;
            height: 48px;
            padding: 0;
            border-radius: 50%;
        }
        
        /* ============================================
           FORM ELEMENTS
           ============================================ */
        .form-group {
            margin-bottom: var(--spacing-lg);
        }
        
        .form-label {
            display: block;
            color: var(--text-white);
            font-weight: 500;
            margin-bottom: var(--spacing-sm);
            font-size: 14px;
        }
        
        .form-label i {
            margin-right: var(--spacing-xs);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: var(--text-white);
            font-size: 16px;
            font-family: inherit;
            transition: all var(--transition-normal);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2);
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            pointer-events: none;
        }
        
        .input-icon input {
            padding-left: 45px;
        }
        
        .input-icon .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            pointer-events: all;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            color: var(--text-white);
            font-size: 14px;
            cursor: pointer;
        }
        
        .form-check input[type="checkbox"],
        .form-check input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .form-error {
            color: #FF6B6B;
            font-size: 13px;
            margin-top: var(--spacing-xs);
            display: none;
        }
        
        .form-error.show {
            display: block;
        }
        
        /* Password Strength Meter */
        .password-strength {
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-sm);
            margin-top: var(--spacing-sm);
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all var(--transition-normal);
        }
        
        .password-strength-bar.weak {
            width: 33%;
            background: var(--danger-color);
        }
        
        .password-strength-bar.medium {
            width: 66%;
            background: var(--warning-color);
        }
        
        .password-strength-bar.strong {
            width: 100%;
            background: var(--success-color);
        }
        
        /* ============================================
           OTP INPUT
           ============================================ */
        .otp-container {
            display: flex;
            gap: var(--spacing-md);
            justify-content: center;
            margin: var(--spacing-xl) 0;
        }
        
        .otp-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: var(--text-white);
            transition: all var(--transition-normal);
        }
        
        .otp-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2);
            transform: scale(1.05);
        }
        
        /* ============================================
           ANIMATIONS
           ============================================ */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-20px);
            }
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
            }
            to {
                transform: translateX(0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
            }
            to {
                transform: translateX(0);
            }
        }
        
        @keyframes slideInUp {
            from {
                transform: translateY(100%);
            }
            to {
                transform: translateY(0);
            }
        }
        
        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
            }
            to {
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
        }
        
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: translateX(-5px);
            }
            20%, 40%, 60%, 80% {
                transform: translateX(5px);
            }
        }
        
        @keyframes gradientShift {
            0%, 100% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        .fade-out {
            animation: fadeOut 0.5s ease;
        }
        
        .slide-in-right {
            animation: slideInRight 0.5s ease;
        }
        
        .slide-in-left {
            animation: slideInLeft 0.5s ease;
        }
        
        .slide-in-up {
            animation: slideInUp 0.5s ease;
        }
        
        .slide-in-down {
            animation: slideInDown 0.5s ease;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        .bounce {
            animation: bounce 1s infinite;
        }
        
        .shake {
            animation: shake 0.5s;
        }
        
        /* ============================================
           LOADING SPINNER
           ============================================ */
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--text-white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: var(--spacing-lg) auto;
        }
        
        .spinner-sm {
            width: 20px;
            height: 20px;
            border-width: 2px;
        }
        
        .spinner-lg {
            width: 60px;
            height: 60px;
            border-width: 6px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: var(--z-modal);
            opacity: 0;
            pointer-events: none;
            transition: opacity var(--transition-normal);
        }
        
        .loading-overlay.show {
            opacity: 1;
            pointer-events: all;
        }
        
        .loading-content {
            text-align: center;
            color: var(--text-white);
        }
        
        .loading-content .spinner {
            margin-bottom: var(--spacing-md);
        }
        
        /* ============================================
           TOAST NOTIFICATIONS
           ============================================ */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: var(--z-tooltip);
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
            max-width: 400px;
        }
        
        .toast {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            animation: slideInRight 0.3s ease;
            min-width: 300px;
        }
        
        .toast.success {
            border-left: 4px solid var(--success-color);
        }
        
        .toast.error {
            border-left: 4px solid var(--danger-color);
        }
        
        .toast.warning {
            border-left: 4px solid var(--warning-color);
        }
        
        .toast.info {
            border-left: 4px solid var(--info-color);
        }
        
        .toast-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .toast.success .toast-icon {
            color: var(--success-color);
        }
        
        .toast.error .toast-icon {
            color: var(--danger-color);
        }
        
        .toast.warning .toast-icon {
            color: var(--warning-color);
        }
        
        .toast.info .toast-icon {
            color: var(--info-color);
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .toast-message {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .toast-close {
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 20px;
            flex-shrink: 0;
            transition: color var(--transition-fast);
        }
        
        .toast-close:hover {
            color: var(--text-primary);
        }
        
        /* ============================================
           MODAL
           ============================================ */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: var(--z-modal);
            display: none;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-lg);
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            position: relative;
            background: white;
            border-radius: var(--radius-xl);
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideInUp 0.3s ease;
            box-shadow: var(--shadow-xl);
        }
        
        .modal-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid #E9ECEF;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .modal-close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: #F8F9FA;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition-fast);
        }
        
        .modal-close:hover {
            background: #E9ECEF;
            color: var(--text-primary);
        }
        
        .modal-body {
            padding: var(--spacing-lg);
        }
        
        .modal-footer {
            padding: var(--spacing-lg);
            border-top: 1px solid #E9ECEF;
            display: flex;
            gap: var(--spacing-md);
            justify-content: flex-end;
        }
        
        /* ============================================
           AUTHENTICATION SECTION
           ============================================ */
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-lg);
        }
        
        .auth-card {
            max-width: 450px;
            width: 100%;
        }
        
        .auth-logo {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }
        
        .auth-logo i {
            font-size: 60px;
            color: var(--text-white);
            margin-bottom: var(--spacing-md);
            display: block;
        }
        
        .auth-logo h1 {
            color: var(--text-white);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
        }
        
        .auth-logo p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }
        
        .auth-tabs {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }
        
        .auth-tab {
            flex: 1;
            padding: var(--spacing-md);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-md);
            color: var(--text-white);
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-normal);
            font-weight: 500;
        }
        
        .auth-tab:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .auth-tab.active {
            background: var(--primary-gradient);
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
        }
        
        .auth-form {
            display: none;
        }
        
        .auth-form.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        .divider {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin: var(--spacing-xl) 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
        }
        
        .social-login {
            display: flex;
            gap: var(--spacing-md);
        }
        
        .social-btn {
            flex: 1;
            padding: var(--spacing-md);
            background: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-normal);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .social-btn img {
            width: 20px;
            height: 20px;
        }
        
        /* ============================================
           MAIN APP LAYOUT
           ============================================ */
        .app-container {
            min-height: 100vh;
            padding-bottom: 80px; /* Space for bottom nav */
        }
        
        .app-header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: var(--spacing-md) var(--spacing-lg);
            position: sticky;
            top: 0;
            z-index: var(--z-sticky);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--spacing-md);
        }
        
        .app-logo {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            color: var(--text-white);
            font-weight: 700;
            font-size: 20px;
            text-decoration: none;
        }
        
        .app-logo i {
            font-size: 24px;
        }
        
        .search-bar {
            flex: 1;
            max-width: 500px;
            position: relative;
        }
        
        .search-bar input {
            width: 100%;
            padding: 10px 40px 10px 40px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-full);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: var(--text-white);
            font-size: 14px;
        }
        
        .search-bar input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .search-bar .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
        }
        
        .search-bar .voice-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .header-icon {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-white);
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .header-icon:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .header-icon .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: var(--radius-full);
            min-width: 18px;
            text-align: center;
        }
        
        /* ============================================
           BOTTOM NAVIGATION
           ============================================ */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            z-index: var(--z-fixed);
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 12px;
            padding: 8px 16px;
            border-radius: var(--radius-lg);
            transition: all var(--transition-fast);
            position: relative;
        }
        
        .nav-item:hover {
            color: var(--text-white);
        }
        
        .nav-item.active {
            background: var(--primary-gradient);
            color: var(--text-white);
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
        }
        
        .nav-icon {
            font-size: 24px;
            margin-bottom: 4px;
        }
        
        .nav-item .badge {
            position: absolute;
            top: 4px;
            right: 8px;
            background: var(--danger-color);
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 5px;
            border-radius: var(--radius-full);
            min-width: 16px;
            text-align: center;
        }
        
        /* ============================================
           UTILITY CLASSES
           ============================================ */
        .hidden {
            display: none !important;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-left {
            text-align: left;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-white {
            color: var(--text-white);
        }
        
        .text-primary {
            color: var(--primary-color);
        }
        
        .text-secondary {
            color: var(--text-secondary);
        }
        
        .text-success {
            color: var(--success-color);
        }
        
        .text-danger {
            color: var(--danger-color);
        }
        
        .text-warning {
            color: var(--warning-color);
        }
        
        .text-small {
            font-size: 14px;
        }
        
        .text-large {
            font-size: 18px;
        }
        
        .font-bold {
            font-weight: 700;
        }
        
        .font-semibold {
            font-weight: 600;
        }
        
        .mt-10 { margin-top: 10px; }
        .mt-20 { margin-top: 20px; }
        .mt-30 { margin-top: 30px; }
        
        .mb-10 { margin-bottom: 10px; }
        .mb-20 { margin-bottom: 20px; }
        .mb-30 { margin-bottom: 30px; }
        
        .p-10 { padding: 10px; }
        .p-20 { padding: 20px; }
        .p-30 { padding: 30px; }
        
        .link {
            color: var(--text-white);
            text-decoration: underline;
            cursor: pointer;
            transition: opacity var(--transition-fast);
        }
        
        .link:hover {
            opacity: 0.8;
        }
        
        /* ============================================
           RESPONSIVE DESIGN
           ============================================ */
        @media (max-width: 768px) {
            .auth-card {
                padding: var(--spacing-md);
            }
            
            .auth-logo h1 {
                font-size: 24px;
            }
            
            .otp-input {
                width: 45px;
                height: 55px;
                font-size: 20px;
            }
            
            .toast-container {
                right: 10px;
                left: 10px;
                max-width: none;
            }
            
            .toast {
                min-width: auto;
            }
            
            .search-bar {
                max-width: none;
            }
            
            .app-logo span {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .otp-container {
                gap: var(--spacing-sm);
            }
            
            .otp-input {
                width: 40px;
                height: 50px;
                font-size: 18px;
            }
            
            .social-login {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Please wait...</p>
        </div>
    </div>
    
    <?php if (!$isLoggedIn): ?>
    <!-- ============================================
         AUTHENTICATION SECTION
         ============================================ -->
    <div id="authSection" class="auth-container">
        <div class="auth-card glass-card fade-in">
            <div class="auth-logo">
                <i class="fas fa-mobile-alt"></i>
                <h1><?php echo APP_NAME; ?></h1>
                <p>Your trusted electronics shopping app</p>
            </div>
            
            <!-- Auth Tabs -->
            <div class="auth-tabs">
                <div class="auth-tab active" onclick="switchAuthTab('login')">
                    <i class="fas fa-sign-in-alt"></i> Login
                </div>
                <div class="auth-tab" onclick="switchAuthTab('register')">
                    <i class="fas fa-user-plus"></i> Register
                </div>
            </div>
            
            <!-- Login Form -->
            <form id="loginForm" class="auth-form active" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" class="form-input" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-input" name="password" id="loginPassword" placeholder="Enter your password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('loginPassword')"></i>
                    </div>
                </div>
                
                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                    <label class="form-check">
                        <input type="checkbox" name="remember"> Remember Me
                    </label>
                    <a href="#" class="link text-small" onclick="showForgotPassword()">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                <div class="divider">OR</div>
                
                <div class="social-login">
                    <button type="button" class="social-btn" onclick="googleSignIn()">
                        <img src="https://www.google.com/favicon.ico" alt="Google">
                        Google
                    </button>
                </div>
            </form>
            
            <!-- Register Form -->
            <form id="registerForm" class="auth-form" onsubmit="handleRegister(event)">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-input" name="name" placeholder="Enter your full name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-phone"></i> Mobile Number
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-phone"></i>
                        <input type="tel" class="form-input" name="phone" placeholder="+91 XXXXX XXXXX" pattern="[0-9]{10}" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" class="form-input" name="email" id="registerEmail" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-input" name="password" id="registerPassword" placeholder="Create a password" required oninput="checkPasswordStrength(this.value)">
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('registerPassword')"></i>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <small class="text-white text-small">At least 8 characters with uppercase, lowercase, and number</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-gift"></i> Referral Code (Optional)
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-gift"></i>
                        <input type="text" class="form-input" name="referral_code" placeholder="Enter referral code">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Register
                </button>
                
                <div class="divider">OR</div>
                
                <div class="social-login">
                    <button type="button" class="social-btn" onclick="googleSignIn()">
                        <img src="https://www.google.com/favicon.ico" alt="Google">
                        Google
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ============================================
         OTP VERIFICATION MODAL
         ============================================ -->
    <div id="otpModal" class="auth-container hidden">
        <div class="auth-card glass-card fade-in">
            <div class="auth-logo">
                <i class="fas fa-shield-alt"></i>
                <h1>Verify Email</h1>
                <p>Enter the 6-digit OTP sent to your email</p>
            </div>
            
            <div class="otp-container">
                <input type="text" class="otp-input" maxlength="1" oninput="moveToNext(this, 0)" onkeydown="moveToPrev(this, event, 0)">
                <input type="text" class="otp-input" maxlength="1" oninput="moveToNext(this, 1)" onkeydown="moveToPrev(this, event, 1)">
                <input type="text" class="otp-input" maxlength="1" oninput="moveToNext(this, 2)" onkeydown="moveToPrev(this, event, 2)">
                <input type="text" class="otp-input" maxlength="1" oninput="moveToNext(this, 3)" onkeydown="moveToPrev(this, event, 3)">
                <input type="text" class="otp-input" maxlength="1" oninput="moveToNext(this, 4)" onkeydown="moveToPrev(this, event, 4)">
                <input type="text" class="otp-input" maxlength="1" oninput="moveToNext(this, 5)" onkeydown="moveToPrev(this, event, 5)">
            </div>
            
            <button class="btn btn-success btn-block" onclick="verifyOTP()">
                <i class="fas fa-check-circle"></i> Verify OTP
            </button>
            
            <div class="text-center mt-20">
                <p class="text-white text-small">
                    Didn't receive OTP? 
                    <span id="resendTimer" class="link">Resend in 60s</span>
                </p>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- ============================================
         MAIN APP SECTION
         ============================================ -->
    <div id="mainApp" class="app-container">
        <!-- Header -->
        <div class="app-header">
            <a href="#" class="app-logo">
                <i class="fas fa-mobile-alt"></i>
                <span><?php echo APP_NAME; ?></span>
            </a>
            
            <div class="search-bar">
                <i class="fas fa-search search-icon"></i>
                <input type="text" placeholder="Search products...">
                <i class="fas fa-microphone voice-icon"></i>
            </div>
            
            <div class="header-actions">
                <div class="header-icon" onclick="showNotifications()">
                    <i class="fas fa-bell"></i>
                    <span class="badge">3</span>
                </div>
                <div class="header-icon" onclick="showProfile()">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div style="padding: 20px; text-align: center; color: white;">
            <h1 class="animate__animated animate__fadeInDown">🎉 Welcome, <?php echo htmlspecialchars($userData['name']); ?>!</h1>
            <p class="animate__animated animate__fadeInUp">You're successfully logged in to <?php echo APP_NAME; ?></p>
            
            <div style="margin-top: 40px;">
                <div class="glass-card" style="max-width: 600px; margin: 0 auto;">
                    <h2 style="color: white; margin-bottom: 20px;">Your Account</h2>
                    <p style="color: rgba(255,255,255,0.9);">
                        <strong>Email:</strong> <?php echo htmlspecialchars($userData['email']); ?><br>
                        <strong>Phone:</strong> <?php echo htmlspecialchars($userData['phone']); ?><br>
                        <strong>User ID:</strong> <?php echo htmlspecialchars($userData['userId']); ?>
                    </p>
                    
                    <div style="margin-top: 30px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <button class="btn btn-primary" onclick="showToast('info', 'Coming Soon', 'This feature is under development')">
                            <i class="fas fa-shopping-cart"></i> Browse Products
                        </button>
                        <button class="btn btn-secondary" onclick="showToast('info', 'Coming Soon', 'This feature is under development')">
                            <i class="fas fa-box"></i> My Orders
                        </button>
                        <button class="btn btn-danger" onclick="handleLogout()">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <a href="#" class="nav-item active">
                <i class="nav-icon fas fa-home"></i>
                Home
            </a>
            <a href="#" class="nav-item">
                <i class="nav-icon fas fa-search"></i>
                Search
            </a>
            <a href="#" class="nav-item">
                <i class="nav-icon fas fa-shopping-cart"></i>
                <span class="badge"><?php echo $cartCount; ?></span>
                Cart
            </a>
            <a href="#" class="nav-item">
                <i class="nav-icon fas fa-heart"></i>
                <span class="badge"><?php echo $wishlistCount; ?></span>
                Wishlist
            </a>
            <a href="#" class="nav-item">
                <i class="nav-icon fas fa-user"></i>
                Profile
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-storage-compat.js"></script>
    
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&libraries=places"></script>
    
    <script>
        // ============================================
        // FIREBASE CONFIGURATION
        // ============================================
        const firebaseConfig = {
            apiKey: "<?php echo FIREBASE_API_KEY; ?>",
            authDomain: "<?php echo FIREBASE_AUTH_DOMAIN; ?>",
            databaseURL: "<?php echo FIREBASE_DATABASE_URL; ?>",
            projectId: "<?php echo FIREBASE_PROJECT_ID; ?>",
            storageBucket: "<?php echo FIREBASE_STORAGE_BUCKET; ?>",
            messagingSenderId: "<?php echo FIREBASE_MESSAGING_SENDER_ID; ?>",
            appId: "<?php echo FIREBASE_APP_ID; ?>"
        };
        
        // Initialize Firebase
        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();
        const database = firebase.database();
        const storage = firebase.storage();
        
        // ============================================
        // UTILITY FUNCTIONS
        // ============================================
        
        /**
         * Show toast notification
         */
        function showToast(type, title, message) {
            const container = document.getElementById('toastContainer');
            
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas ${icons[type]}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <div class="toast-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </div>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideInRight 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
        
        /**
         * Show loading overlay
         */
        function showLoading(show = true) {
            const overlay = document.getElementById('loadingOverlay');
            if (show) {
                overlay.classList.add('show');
            } else {
                overlay.classList.remove('show');
            }
        }
        
        /**
         * Switch between login and register tabs
         */
        function switchAuthTab(tab) {
            const tabs = document.querySelectorAll('.auth-tab');
            const forms = document.querySelectorAll('.auth-form');
            
            tabs.forEach(t => t.classList.remove('active'));
            forms.forEach(f => f.classList.remove('active'));
            
            if (tab === 'login') {
                tabs[0].classList.add('active');
                document.getElementById('loginForm').classList.add('active');
            } else {
                tabs[1].classList.add('active');
                document.getElementById('registerForm').classList.add('active');
            }
        }
        
        /**
         * Toggle password visibility
         */
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        /**
         * Check password strength
         */
        function checkPasswordStrength(password) {
            const bar = document.getElementById('passwordStrengthBar');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            bar.className = 'password-strength-bar';
            
            if (strength <= 2) {
                bar.classList.add('weak');
            } else if (strength <= 4) {
                bar.classList.add('medium');
            } else {
                bar.classList.add('strong');
            }
        }
        
        /**
         * Handle login form submission
         */
        async function handleLogin(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            formData.append('action', 'login');
            
            showLoading(true);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('success', 'Success', result.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('error', 'Login Failed', result.message);
                }
            } catch (error) {
                showToast('error', 'Error', 'An error occurred. Please try again.');
            } finally {
                showLoading(false);
            }
        }
        
        /**
         * Handle register form submission
         */
        async function handleRegister(event) {
            event.preventDefault();
            
            const form = event.target;
            const email = form.querySelector('[name="email"]').value;
            const name = form.querySelector('[name="name"]').value;
            
            // First send OTP
            const otpFormData = new FormData();
            otpFormData.append('action', 'send_otp');
            otpFormData.append('email', email);
            otpFormData.append('name', name);
            
            showLoading(true);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: otpFormData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('success', 'OTP Sent', result.message);
                    
                    // Store form data for later
                    window.registrationData = new FormData(form);
                    
                    // Show OTP modal
                    document.getElementById('authSection').classList.add('hidden');
                    document.getElementById('otpModal').classList.remove('hidden');
                    
                    // Start resend timer
                    startResendTimer();
                } else {
                    showToast('error', 'Error', result.message);
                }
            } catch (error) {
                showToast('error', 'Error', 'Failed to send OTP. Please try again.');
            } finally {
                showLoading(false);
            }
        }
        
        /**
         * Move to next OTP input
         */
        function moveToNext(current, index) {
            if (current.value.length === 1 && index < 5) {
                const inputs = document.querySelectorAll('.otp-input');
                inputs[index + 1].focus();
            }
            
            // Auto-submit when all 6 digits are entered
            const allInputs = document.querySelectorAll('.otp-input');
            const allFilled = Array.from(allInputs).every(input => input.value.length === 1);
            
            if (allFilled) {
                verifyOTP();
            }
        }
        
        /**
         * Move to previous OTP input on backspace
         */
        function moveToPrev(current, event, index) {
            if (event.key === 'Backspace' && current.value.length === 0 && index > 0) {
                const inputs = document.querySelectorAll('.otp-input');
                inputs[index - 1].focus();
            }
        }
        
        /**
         * Verify OTP
         */
        async function verifyOTP() {
            const inputs = document.querySelectorAll('.otp-input');
            const otp = Array.from(inputs).map(input => input.value).join('');
            
            if (otp.length !== 6) {
                showToast('warning', 'Incomplete', 'Please enter all 6 digits');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'verify_otp');
            formData.append('otp', otp);
            
            showLoading(true);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('success', 'Verified', result.message);
                    
                    // Now complete registration
                    if (window.registrationData) {
                        window.registrationData.append('action', 'register');
                        
                        const regResponse = await fetch('', {
                            method: 'POST',
                            body: window.registrationData
                        });
                        
                        const regResult = await regResponse.json();
                        
                        if (regResult.success) {
                            showToast('success', 'Success', 'Registration completed successfully!');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showToast('error', 'Error', regResult.message);
                        }
                    } else {
                        // Just email verification
                        setTimeout(() => {
                            document.getElementById('otpModal').classList.add('hidden');
                            document.getElementById('authSection').classList.remove('hidden');
                        }, 1000);
                    }
                } else {
                    showToast('error', 'Verification Failed', result.message);
                    
                    // Clear OTP inputs
                    inputs.forEach(input => input.value = '');
                    inputs[0].focus();
                }
            } catch (error) {
                showToast('error', 'Error', 'Failed to verify OTP. Please try again.');
            } finally {
                showLoading(false);
            }
        }
        
        /**
         * Start resend timer
         */
        function startResendTimer() {
            let seconds = 60;
            const timerElement = document.getElementById('resendTimer');
            
            const interval = setInterval(() => {
                seconds--;
                timerElement.textContent = `Resend in ${seconds}s`;
                
                if (seconds <= 0) {
                    clearInterval(interval);
                    timerElement.textContent = 'Resend OTP';
                    timerElement.style.cursor = 'pointer';
                    timerElement.onclick = resendOTP;
                }
            }, 1000);
        }
        
        /**
         * Resend OTP
         */
        async function resendOTP() {
            const formData = new FormData();
            formData.append('action', 'resend_otp');
            
            showLoading(true);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('success', 'OTP Resent', result.message);
                    startResendTimer();
                } else {
                    showToast('error', 'Error', result.message);
                }
            } catch (error) {
                showToast('error', 'Error', 'Failed to resend OTP. Please try again.');
            } finally {
                showLoading(false);
            }
        }
        
        /**
         * Google Sign-In
         */
        async function googleSignIn() {
            const provider = new firebase.auth.GoogleAuthProvider();
            
            showLoading(true);
            
            try {
                const result = await auth.signInWithPopup(provider);
                showToast('success', 'Success', 'Signed in with Google!');
                
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                
            } catch (error) {
                showToast('error', 'Sign-In Failed', error.message);
            } finally {
                showLoading(false);
            }
        }
        
        /**
         * Handle logout
         */
        async function handleLogout() {
            if (!confirm('Are you sure you want to logout?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'logout');
            
            showLoading(true);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('success', 'Success', result.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } catch (error) {
                showToast('error', 'Error', 'Failed to logout');
            } finally {
                showLoading(false);
            }
        }
        
        /**
         * Show forgot password
         */
        function showForgotPassword() {
            showToast('info', 'Coming Soon', 'Password reset feature is under development');
        }
        
        /**
         * Show notifications
         */
        function showNotifications() {
            showToast('info', 'Notifications', 'You have 3 new notifications');
        }
        
        /**
         * Show profile
         */
        function showProfile() {
            showToast('info', 'Profile', 'Profile page is under development');
        }
        
        // ============================================
        // SERVICE WORKER REGISTRATION (PWA)
        // ============================================
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(registration => {
                        console.log('Service Worker registered:', registration);
                    })
                    .catch(error => {
                        console.log('Service Worker registration failed:', error);
                    });
            });
        }
        
        // ============================================
        // INITIALIZE APP
        // ============================================
        document.addEventListener('DOMContentLoaded', () => {
            console.log('<?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?> loaded');
            
            // Check authentication state
            auth.onAuthStateChanged(user => {
                if (user) {
                    console.log('User logged in:', user.email);
                } else {
                    console.log('User not logged in');
                }
            });
        });
    </script>
</body>
</html>