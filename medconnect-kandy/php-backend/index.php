<?php
session_start();
require_once 'config/database.php';
require_once 'helpers/security.php';
require_once 'helpers/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($_SESSION['user']['role'] === 'doctor') {
        header('Location: doctor/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user || !verifyPassword($password, $user['hashed_password'])) {
                $error = 'Invalid email or password';
            } elseif (!$user['is_active']) {
                if ($user['role'] === 'doctor' && !$user['is_verified']) {
                    $error = 'Your doctor account is pending admin verification. Please wait for approval.';
                } else {
                    $error = 'Your account has been deactivated. Please contact support.';
                }
            } else {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'role' => $user['role'],
                    'specialization' => $user['specialization'] ?? null,
                    'is_active' => $user['is_active'],
                    'is_verified' => $user['is_verified'],
                    'created_at' => $user['created_at']
                ];
                
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } elseif ($user['role'] === 'doctor') {
                    header('Location: doctor/dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LocalDoc Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex items-center justify-center relative overflow-hidden"
      style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 25%, #3b82f6 50%, #2563eb 75%, #1d4ed8 100%);
             background-size: 400% 400%;
             animation: gradientShift 15s ease infinite;">

    <!-- Animated background elements -->
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
        @keyframes pulse {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.1); }
        }
    </style>
    
    <!-- Floating circles -->
    <div class="absolute top-20 left-10 w-32 h-32 bg-white/10 rounded-full blur-xl" style="animation: float 6s ease-in-out infinite;"></div>
    <div class="absolute top-40 right-20 w-24 h-24 bg-white/15 rounded-full blur-lg" style="animation: float 8s ease-in-out infinite 1s;"></div>
    <div class="absolute bottom-32 left-1/4 w-40 h-40 bg-white/10 rounded-full blur-2xl" style="animation: float 7s ease-in-out infinite 0.5s;"></div>
    <div class="absolute bottom-20 right-1/3 w-28 h-28 bg-white/12 rounded-full blur-xl" style="animation: float 9s ease-in-out infinite 2s;"></div>
    <div class="absolute top-1/3 left-1/2 w-20 h-20 bg-white/20 rounded-full blur-lg" style="animation: float 5s ease-in-out infinite 1.5s;"></div>
    
    <!-- Medical icons -->
    <div class="absolute top-1/4 left-1/4 text-white/15 text-7xl" style="animation: float 8s ease-in-out infinite;">
        <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
        </svg>
    </div>
    <div class="absolute bottom-1/3 right-1/4 text-white/15 text-6xl" style="animation: float 10s ease-in-out infinite 1s;">
        <svg class="w-14 h-14" fill="currentColor" viewBox="0 0 24 24">
            <path d="M19 3H5c-1.1 0-1.99.9-1.99 2L3 19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/>
        </svg>
    </div>
    <div class="absolute top-1/2 right-1/3 text-white/12 text-5xl" style="animation: float 7s ease-in-out infinite 2s;">
        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
            <path d="M10.5 13H8v-3h2.5V7.5h3V10H16v3h-2.5v2.5h-3V13zM12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3z"/>
        </svg>
    </div>
    <div class="absolute top-20 left-1/3 text-white/10 text-6xl" style="animation: pulse 6s ease-in-out infinite 0.5s;">
        <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
            <path d="M19 8h-2v3h-3v2h3v3h2v-3h3v-2h-3V8zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16 4h-2v4l-4.8 6L12 18l2-4h4V6h2v4z"/>
        </svg>
    </div>
    <div class="absolute bottom-40 left-20 text-white/10 text-5xl" style="animation: pulse 8s ease-in-out infinite 1.5s;">
        <svg class="w-14 h-14" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
        </svg>
    </div>
    <div class="absolute top-1/3 right-20 text-white/12 text-6xl" style="animation: float 9s ease-in-out infinite 3s;">
        <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
        </svg>
    </div>

    <!-- Dark overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-black/30 via-black/20 to-black/40"></div>

    <!-- Login Card -->
    <div class="relative bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md z-10">

        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg viewBox="0 0 32 32" fill="none" class="w-10 h-10">
                    <path d="M16 8V24M8 16H24" stroke="white" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900">Welcome Back</h1>
            <p class="text-slate-500 mt-2">Sign in to your LocalDoc account</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                <input type="email" name="email" required
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="you@example.com">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" id="remember" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <label for="remember" class="ml-2 text-sm text-slate-600">Remember me</label>
                </div>
                <a href="forgot-password.php" class="text-sm text-blue-600 font-semibold hover:underline">Forgot Password?</a>
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold py-3 rounded-xl hover:shadow-lg transition">
                Sign In
            </button>
        </form>

        <p class="text-center mt-6 text-slate-600">
            Don't have an account?
            <a href="register.php" class="text-blue-600 font-semibold hover:underline">Create one</a>
        </p>

        <p class="text-center mt-3 text-slate-600">
            Are you a doctor?
            <a href="doctor/register.php" class="text-green-600 font-semibold hover:underline">Register here</a>
        </p>

    </div>

</body>
</html>