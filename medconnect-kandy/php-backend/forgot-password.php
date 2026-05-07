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

// Handle forgot password request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    if (!checkRateLimit('forgot_password_' . $_SERVER['REMOTE_ADDR'], 3, RATE_LIMIT_WINDOW)) {
        $error = 'Too many requests. Please try again later.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Email is required';
        } elseif (!isValidEmail($email)) {
            $error = 'Invalid email format';
        } else {
            try {
                $db = getDBConnection();
                $stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    // Don't reveal if email exists or not (security best practice)
                    $success = 'If an account exists with that email, a reset link has been sent.';
                } else {
                    // Generate reset token
                    $reset_token = generateSecureToken(32);
                    $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store reset token in database
                    $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                    $stmt->execute([$reset_token, $reset_expires, $user['id']]);
                    
                    // In production, send email with reset link
                    // For demo, show the reset link directly
                    $basePath = dirname($_SERVER['PHP_SELF']);
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $reset_link = $protocol . '://' . $host . $basePath . '/reset-password.php?token=' . $reset_token;
                    
                    $success = 'Password reset link generated! In production, this would be emailed to you. For demo: <br><br><a href="' . $reset_link . '" class="inline-block bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700">Click here to reset password</a>';
                }
            } catch (PDOException $e) {
                $error = 'Failed to process request. Please try again.';
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
    <title>Forgot Password - LocalDoc Connect</title>
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

    <!-- Forgot Password Card -->
    <div class="relative bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md z-10">

        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900">Forgot Password?</h1>
            <p class="text-slate-500 mt-2">Enter your email to reset your password</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                <input type="email" name="email" required
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500"
                    placeholder="you@example.com">
                <p class="text-xs text-slate-500 mt-2">We'll send you a password reset link</p>
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-orange-500 to-red-500 text-white font-bold py-3 rounded-xl hover:shadow-lg transition">
                Send Reset Link
            </button>
        </form>

        <p class="text-center mt-6 text-slate-600">
            Remember your password?
            <a href="index.php" class="text-blue-600 font-semibold hover:underline">Back to Login</a>
        </p>

    </div>

</body>
</html>
