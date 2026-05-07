<?php
session_start();
require_once 'config/database.php';
require_once 'helpers/security.php';
require_once 'helpers/functions.php';
require_once 'middleware/auth.php';

// Set security headers
setSecurityHeaders();

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    if (!checkRateLimit('register_' . $_SERVER['REMOTE_ADDR'], 3, RATE_LIMIT_WINDOW)) {
        $error = 'Too many registration attempts. Please try again later.';
    } else {
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($full_name) || empty($email) || empty($password)) {
            $error = 'All required fields must be filled';
        } elseif (!isValidEmail($email)) {
            $error = 'Invalid email format';
        } elseif (!isPasswordStrong($password)) {
            $error = getPasswordStrengthMessage($password);
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } else {
            try {
                $db = getDBConnection();
                
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'An account with this email already exists';
                } elseif (!empty($phone)) {
                    $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
                    $stmt->execute([$phone]);
                    if ($stmt->fetch()) {
                        $error = 'An account with this phone number already exists';
                    } else {
                        $hashedPassword = hashPassword($password);
                        $stmt = $db->prepare("
                            INSERT INTO users (full_name, email, phone, hashed_password, role, is_active, is_verified, created_at)
                            VALUES (?, ?, ?, ?, 'patient', 1, 0, NOW())
                        ");
                        $stmt->execute([$full_name, $email, $phone, $hashedPassword]);
                        
                        // Regenerate session ID
                        session_regenerate_id(true);
                        
                        $_SESSION['user'] = [
                            'id' => $db->lastInsertId(),
                            'full_name' => $full_name,
                            'email' => $email,
                            'phone' => $phone,
                            'role' => 'patient',
                            'is_active' => true,
                            'is_verified' => false
                        ];
                        
                        // Regenerate CSRF token
                        regenerateCSRFToken();
                        
                        header('Location: dashboard.php');
                        exit;
                    }
                } else {
                    $hashedPassword = hashPassword($password);
                    $stmt = $db->prepare("
                        INSERT INTO users (full_name, email, phone, hashed_password, role, is_active, is_verified, created_at)
                        VALUES (?, ?, ?, ?, 'patient', 1, 0, NOW())
                    ");
                    $stmt->execute([$full_name, $email, null, $hashedPassword]);
                    
                    // Regenerate session ID
                    session_regenerate_id(true);
                    
                    $_SESSION['user'] = [
                        'id' => $db->lastInsertId(),
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => null,
                        'role' => 'patient',
                        'is_active' => true,
                        'is_verified' => false
                    ];
                    
                    // Regenerate CSRF token
                    regenerateCSRFToken();
                    
                    header('Location: dashboard.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
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
    <title>Register - LocalDoc Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center py-12">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-slate-900">Create Account</h1>
            <p class="text-slate-500 mt-2">Join thousands of patients in Kandy</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Full Name</label>
                <input type="text" name="full_name" required 
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Kamal Perera">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                <input type="email" name="email" required 
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="you@example.com">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Phone <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="tel" name="phone" 
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="+94 77 000 0000">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                <input type="password" name="password" required 
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Min. 8 characters">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Confirm Password</label>
                <input type="password" name="confirm_password" required 
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Re-enter password">
            </div>

            <button type="submit" 
                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold py-3 rounded-xl hover:shadow-lg transition-all mt-6">
                Create Account
            </button>
        </form>

        <p class="text-center mt-6 text-slate-600">
            Already have an account? 
            <a href="index.php" class="text-blue-600 font-semibold hover:underline">Sign in</a>
        </p>
    </div>
</body>
</html>
