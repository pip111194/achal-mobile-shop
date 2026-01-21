<?php
/**
 * Achal Mobile Shop - Customer App (Simplified Single File)
 * Version: 1.0
 * Description: Complete customer-facing e-commerce PWA
 */

session_start();

// ============================================
// CONFIGURATION
// ============================================

// Email SMTP Configuration
$smtp_host = 'smtp.gmail.com';
$smtp_port = 465;
$smtp_username = 'pranshurathaor285@gmail.com';
$smtp_password = 'btfnvjkjhbozihbo';

// Master API Key
$masterApiKey = 'achal_key-7i3ry8ioio2e3yfuu9ipo7uttrfew';

// ============================================
// PHP FUNCTIONS
// ============================================

/**
 * Generate 6-digit OTP
 */
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send OTP via Email
 */
function sendOTPEmail($to, $otp) {
    global $smtp_username;
    
    $subject = "Your Achal Mobile Shop OTP";
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
            .email-container {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 40px 20px;
            }
            .otp-box {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 16px;
                padding: 40px;
                text-align: center;
                max-width: 500px;
                margin: 0 auto;
                box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            }
            .logo { font-size: 24px; font-weight: bold; color: #667eea; margin-bottom: 20px; }
            .otp-code {
                font-size: 48px;
                font-weight: bold;
                color: #667eea;
                letter-spacing: 10px;
                margin: 30px 0;
                padding: 20px;
                background: #f0f0f0;
                border-radius: 12px;
            }
            .message { color: #333; font-size: 16px; line-height: 1.6; }
            .footer { margin-top: 30px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='otp-box'>
                <div class='logo'>📱 Achal Mobile Shop</div>
                <h2 style='color: #333;'>Verify Your Email</h2>
                <p class='message'>Your One-Time Password (OTP) is:</p>
                <div class='otp-code'>$otp</div>
                <p class='message'>This OTP is valid for <strong>10 minutes</strong>.</p>
                <p class='message'>If you didn't request this, please ignore this email.</p>
                <div class='footer'>
                    <p>Thank you for choosing Achal Mobile Shop!</p>
                    <p>© 2024 Achal Mobile Shop. All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Achal Mobile Shop <$smtp_username>\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Send activity to Admin Dashboard
 */
function sendToAdmin($action, $data) {
    global $masterApiKey;
    
    $url = 'https://your-api-endpoint.com/api/customer/activity';
    
    $payload = json_encode([
        'userId' => $_SESSION['userId'] ?? 'guest',
        'action' => $action,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . $masterApiKey,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'response' => json_decode($response, true)];
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// ============================================
// AJAX REQUEST HANDLER
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'send_otp':
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                exit;
            }
            
            $otp = generateOTP();
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_time'] = time();
            $_SESSION['otp_email'] = $email;
            
            if (sendOTPEmail($email, $otp)) {
                sendToAdmin('otp_sent', ['email' => $email]);
                echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
            }
            exit;
            
        case 'verify_otp':
            $inputOTP = sanitize($_POST['otp']);
            $sessionOTP = $_SESSION['otp'] ?? '';
            $otpTime = $_SESSION['otp_time'] ?? 0;
            
            if (time() - $otpTime > 600) {
                echo json_encode(['success' => false, 'message' => 'OTP expired. Please request a new one.']);
            } elseif ($inputOTP === $sessionOTP) {
                $_SESSION['email_verified'] = true;
                sendToAdmin('otp_verified', ['email' => $_SESSION['otp_email']]);
                unset($_SESSION['otp'], $_SESSION['otp_time']);
                echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
            }
            exit;
            
        case 'resend_otp':
            $lastSent = $_SESSION['otp_time'] ?? 0;
            
            if (time() - $lastSent < 60) {
                $remaining = 60 - (time() - $lastSent);
                echo json_encode(['success' => false, 'message' => "Please wait $remaining seconds before resending"]);
                exit;
            }
            
            $email = $_SESSION['otp_email'] ?? '';
            if (empty($email)) {
                echo json_encode(['success' => false, 'message' => 'No email found. Please start over.']);
                exit;
            }
            
            $otp = generateOTP();
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_time'] = time();
            
            if (sendOTPEmail($email, $otp)) {
                echo json_encode(['success' => true, 'message' => 'OTP resent successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to resend OTP']);
            }
            exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <meta name="description" content="Achal Mobile Shop - Your trusted electronics shopping app">
    
    <title>Achal Mobile Shop - Customer App</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- Icons -->
    <link rel="icon" type="image/png" href="icon-192.png">
    <link rel="apple-touch-icon" href="icon-192.png">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ============================================
           GLOBAL STYLES
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-gradient: linear-gradient(135deg, #FF6B35 0%, #FFA726 100%);
            --secondary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #00B894 0%, #00D2A0 100%);
            --danger-gradient: linear-gradient(135deg, #FF6B6B 0%, #EE5A6F 100%);
            --warning-gradient: linear-gradient(135deg, #FFA726 0%, #FFB74D 100%);
            --info-gradient: linear-gradient(135deg, #0984E3 0%, #74B9FF 100%);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
            color: #333;
        }
        
        /* ============================================
           GLASSMORPHISM COMPONENTS
           ============================================ */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.45);
        }
        
        .glass-header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 15px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        /* ============================================
           BUTTONS
           ============================================ */
        .btn {
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.6);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: var(--secondary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: var(--success-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 184, 148, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline:hover {
            background: white;
            color: #667eea;
        }
        
        /* ============================================
           FORM ELEMENTS
           ============================================ */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            color: white;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-input:focus {
            outline: none;
            border-color: #FF6B35;
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
        }
        
        .input-icon input {
            padding-left: 45px;
        }
        
        /* ============================================
           OTP INPUT
           ============================================ */
        .otp-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 30px 0;
        }
        
        .otp-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            transition: all 0.3s ease;
        }
        
        .otp-input:focus {
            outline: none;
            border-color: #FF6B35;
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2);
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
        
        @keyframes slideIn {
            from {
                transform: translateX(-100%);
            }
            to {
                transform: translateX(0);
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
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        .slide-in {
            animation: slideIn 0.5s ease;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        /* ============================================
           LOADING SPINNER
           ============================================ */
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        /* ============================================
           TOAST NOTIFICATIONS
           ============================================ */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
            max-width: 350px;
        }
        
        .toast.success {
            border-left: 4px solid #00B894;
        }
        
        .toast.error {
            border-left: 4px solid #FF6B6B;
        }
        
        .toast.warning {
            border-left: 4px solid #FFA726;
        }
        
        .toast.info {
            border-left: 4px solid #0984E3;
        }
        
        .toast-icon {
            font-size: 24px;
        }
        
        .toast.success .toast-icon {
            color: #00B894;
        }
        
        .toast.error .toast-icon {
            color: #FF6B6B;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        
        .toast-message {
            font-size: 14px;
            color: #666;
        }
        
        /* ============================================
           AUTHENTICATION SECTION
           ============================================ */
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-card {
            max-width: 450px;
            width: 100%;
        }
        
        .auth-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-logo i {
            font-size: 60px;
            color: white;
            margin-bottom: 10px;
        }
        
        .auth-logo h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
        }
        
        .auth-logo p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }
        
        .auth-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .auth-tab {
            flex: 1;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
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
        }
        
        .divider {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 25px 0;
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
            gap: 10px;
        }
        
        .social-btn {
            flex: 1;
            padding: 12px;
            background: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .social-btn img {
            width: 20px;
            height: 20px;
        }
        
        /* ============================================
           RESPONSIVE DESIGN
           ============================================ */
        @media (max-width: 768px) {
            .auth-card {
                padding: 20px;
            }
            
            .auth-logo h1 {
                font-size: 24px;
            }
            
            .otp-input {
                width: 45px;
                height: 55px;
                font-size: 20px;
            }
            
            .toast {
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
        
        /* ============================================
           UTILITY CLASSES
           ============================================ */
        .text-center {
            text-align: center;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
        
        .mb-20 {
            margin-bottom: 20px;
        }
        
        .hidden {
            display: none !important;
        }
        
        .text-white {
            color: white;
        }
        
        .text-small {
            font-size: 14px;
        }
        
        .link {
            color: white;
            text-decoration: underline;
            cursor: pointer;
        }
        
        .link:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    
    <!-- ============================================
         AUTHENTICATION SECTION
         ============================================ -->
    <div id="authSection" class="auth-container">
        <div class="auth-card glass-card fade-in">
            <div class="auth-logo">
                <i class="fas fa-mobile-alt"></i>
                <h1>Achal Mobile Shop</h1>
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
                        <input type="email" class="form-input" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-input" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                    <label style="color: white; font-size: 14px; cursor: pointer;">
                        <input type="checkbox" style="margin-right: 5px;"> Remember Me
                    </label>
                    <a href="#" class="link text-small">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
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
                        <input type="text" class="form-input" placeholder="Enter your full name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-phone"></i> Mobile Number
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-phone"></i>
                        <input type="tel" class="form-input" placeholder="+91 XXXXX XXXXX" pattern="[0-9]{10}" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="registerEmail" class="form-input" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-input" placeholder="Create a password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-gift"></i> Referral Code (Optional)
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-gift"></i>
                        <input type="text" class="form-input" placeholder="Enter referral code">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
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
                <input type="text" class="otp-input" maxlength="1" oninput="moveToNext(this, 1)">
                <input type="text" class="otp-input" maxlength="1" oninput="moveToNext(this, 2)">
                <input type="text" class="otp-input" maxlength="1" oninput="moveToNext(this, 3)">
                <input type="text" class="otp-input" maxlength="1" oninput="moveToNext(this, 4)">
                <input type="text" class="otp-input" maxlength="1" oninput="moveToNext(this, 5)">
                <input type="text" class="otp-input" maxlength="1" oninput="moveToNext(this, 6)">
            </div>
            
            <button class="btn btn-success" style="width: 100%;" onclick="verifyOTP()">
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
    
    <!-- ============================================
         MAIN APP SECTION (Hidden initially)
         ============================================ -->
    <div id="mainApp" class="hidden">
        <div class="glass-header">
            <h2 style="color: white;">Welcome to Achal Mobile Shop!</h2>
        </div>
        <div style="padding: 20px; text-align: center; color: white;">
            <h1>🎉 You're logged in!</h1>
            <p>Main app features will be loaded here...</p>
        </div>
    </div>
    
    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    
    <script>
        // ============================================
        // FIREBASE CONFIGURATION
        // ============================================
        const firebaseConfig = {
            apiKey: "AIzaSyBbTaQvWY9Z1DfXI8SGXQlfzplPFe3TLPg",
            authDomain: "mobile-shop-e8bd6.firebaseapp.com",
            databaseURL: "https://mobile-shop-e8bd6-default-rtdb.firebaseio.com",
            projectId: "mobile-shop-e8bd6",
            storageBucket: "mobile-shop-e8bd6.firebasestorage.app",
            messagingSenderId: "903890636840",
            appId: "1:903890636840:web:29d9a6cd9be6526638a51d"
        };
        
        // Initialize Firebase
        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();
        const database = firebase.database();
        
        // ============================================
        // UTILITY FUNCTIONS
        // ============================================
        
        /**
         * Show toast notification
         */
        function showToast(type, title, message) {
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
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
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
         * Handle login form submission
         */
        async function handleLogin(event) {
            event.preventDefault();
            
            const form = event.target;
            const email = form.querySelector('input[type="email"]').value;
            const password = form.querySelector('input[type="password"]').value;
            
            try {
                const userCredential = await auth.signInWithEmailAndPassword(email, password);
                showToast('success', 'Success', 'Logged in successfully!');
                
                // Hide auth section and show main app
                document.getElementById('authSection').classList.add('hidden');
                document.getElementById('mainApp').classList.remove('hidden');
                
            } catch (error) {
                showToast('error', 'Login Failed', error.message);
            }
        }
        
        /**
         * Handle register form submission
         */
        async function handleRegister(event) {
            event.preventDefault();
            
            const form = event.target;
            const email = form.querySelector('#registerEmail').value;
            
            // Send OTP
            const formData = new FormData();
            formData.append('action', 'send_otp');
            formData.append('email', email);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('success', 'OTP Sent', result.message);
                    
                    // Show OTP modal
                    document.getElementById('authSection').classList.add('hidden');
                    document.getElementById('otpModal').classList.remove('hidden');
                    
                    // Start resend timer
                    startResendTimer();
                } else {
                    showToast('error', 'Error', result.message);
                }
            } catch (error) {
                showToast('error', 'Error', 'Failed to send OTP');
            }
        }
        
        /**
         * Move to next OTP input
         */
        function moveToNext(current, index) {
            if (current.value.length === 1 && index < 6) {
                const inputs = document.querySelectorAll('.otp-input');
                inputs[index].focus();
            }
            
            // Auto-submit when all 6 digits are entered
            const allInputs = document.querySelectorAll('.otp-input');
            const allFilled = Array.from(allInputs).every(input => input.value.length === 1);
            
            if (allFilled) {
                verifyOTP();
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
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('success', 'Verified', result.message);
                    
                    // Hide OTP modal and show main app
                    document.getElementById('otpModal').classList.add('hidden');
                    document.getElementById('mainApp').classList.remove('hidden');
                } else {
                    showToast('error', 'Verification Failed', result.message);
                    
                    // Clear OTP inputs
                    inputs.forEach(input => input.value = '');
                    inputs[0].focus();
                }
            } catch (error) {
                showToast('error', 'Error', 'Failed to verify OTP');
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
                showToast('error', 'Error', 'Failed to resend OTP');
            }
        }
        
        /**
         * Google Sign-In
         */
        async function googleSignIn() {
            const provider = new firebase.auth.GoogleAuthProvider();
            
            try {
                const result = await auth.signInWithPopup(provider);
                showToast('success', 'Success', 'Signed in with Google!');
                
                // Hide auth section and show main app
                document.getElementById('authSection').classList.add('hidden');
                document.getElementById('mainApp').classList.remove('hidden');
                
            } catch (error) {
                showToast('error', 'Sign-In Failed', error.message);
            }
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
    </script>
</body>
</html>