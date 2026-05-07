<?php
session_start();
require_once 'config/database.php';
require_once 'helpers/security.php';
require_once 'helpers/functions.php';
require_once 'middleware/auth.php';

// Set security headers
setSecurityHeaders();

$error = '';
$success = '';
$valid_token = false;
$token = $_GET['token'] ?? '';

// Verify token on page load
if (!empty($token)) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $valid_token = true;
        } else {
            $error = 'Invalid or expired reset link. Please request a new one.';
        }
    } catch (PDOException $e) {
        $error = 'Failed to verify reset link.';
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    // Rate limiting
    if (!checkRateLimit('reset_password_' . $_SERVER['REMOTE_ADDR'], 3, RATE_LIMIT_WINDOW)) {
        $error = 'Too many attempts. Please try again later.';
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = 'Both password are required';
        } elseif (!isPasswordStrong($new_password)) {
            $error = getPasswordStrengthMessage($new_password);
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match';
        } else {
            try {
                $db = getDBConnection();
                $hashedPassword = hashPassword($new_password);
                
                // Update password and clear reset token
                $stmt = $db->prepare("UPDATE users SET hashed_password = ?, reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ?");
                $stmt->execute([$hashedPassword, $token]);
                
                $success = 'Password reset successful! You can now login with your new password.';
                $valid_token = false; // Hide form after success
            } catch (PDOException $e) {
                $error = 'Failed to reset password. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - LocalDoc Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex items-center justify-center relative overflow-hidden"
      style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 25%, #3b82f6 50%, #2563eb 75%, #1d4ed8 100%);
             background-size: 400% 400%;
             animation: gradientShift 15s ease infinite;">

    <style>
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
    </style>
    
    <!-- Floating circles -->
    <div class="absolute top-20 left-10 w-32 h-32 bg-white/10 rounded-full blur-xl" style="animation: float 6s ease-in-out infinite;"></div>
    <div class="absolute top-40 right-20 w-24 h-24 bg-white/15 rounded-full blur-lg" style="animation: float 8s ease-in-out infinite 1s;"></div>
    <div class="absolute bottom-32 left-1/4 w-40 h-40 bg-white/10 rounded-full blur-2xl" style="animation: float 7s ease-in-out infinite 0.5s;"></div>
    <div class="absolute bottom-20 right-1/3 w-28 h-28 bg-white/12 rounded-full blur-xl" style="animation: float 9s ease-in-out infinite 2s;"></div>

    <!-- Dark overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-black/30 via-black/20 to-black/40"></div>

    <!-- Reset Password Card -->
    <div class="relative bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md z-10">

        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-teal-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900">Reset Password</h1>
            <p class="text-slate-500 mt-2">Create a new password for your account</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <div class="text-center">
                <a href="index.php" class="inline-block bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold py-3 px-8 rounded-xl hover:shadow-lg transition">
                    Go to Login
                </a>
            </div>
        <?php endif; ?>

        <?php if ($valid_token && !$success): ?>
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">New Password</label>
                <input type="password" name="new_password" required minlength="8"
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="Min. 8 characters">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="8"
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="Re-enter new password">
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-green-500 to-teal-500 text-white font-bold py-3 rounded-xl hover:shadow-lg transition">
                Reset Password
            </button>
        </form>
        <?php endif; ?>

        <?php if (!$valid_token && !$success): ?>
        <div class="text-center">
            <a href="forgot-password.php" class="text-blue-600 font-semibold hover:underline">Request a new reset link</a>
        </div>
        <?php endif; ?>

    </div>

</body>
</html>
