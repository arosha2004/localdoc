<?php
session_start();
require_once 'config/database.php';
require_once 'helpers/security.php';
require_once 'helpers/functions.php';

// Check authentication
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$db = getDBConnection();

// Determine redirect paths based on role
$dashboardLink = $user['role'] === 'doctor' ? 'doctor/dashboard.php' : 'dashboard.php';
$backLabel = $user['role'] === 'doctor' ? 'Back to Dashboard' : 'Back to Dashboard';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $dashboardLink);
    exit;
}

$error = '';
$success = '';

// Handle profile update (limited: name and phone only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    if (empty($full_name)) {
        $error = 'Name is required';
    } else {
        try {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $user['id']]);
            
            // Update session
            $_SESSION['user']['full_name'] = $full_name;
            $_SESSION['user']['phone'] = $phone;
            
            $success = 'Profile updated successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to update profile';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters';
    } else {
        // Verify current password
        $stmt = $db->prepare("SELECT hashed_password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $userData = $stmt->fetch();
        
        if (!verifyPassword($current_password, $userData['hashed_password'])) {
            $error = 'Current password is incorrect';
        } else {
            $hashedPassword = hashPassword($new_password);
            $stmt = $db->prepare("UPDATE users SET hashed_password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            $success = 'Password changed successfully!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - LocalDoc Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-md px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                <svg viewBox="0 0 32 32" fill="none" class="w-6 h-6">
                    <path d="M16 8V24M8 16H24" stroke="white" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>
            <span class="text-xl font-bold text-slate-900">LocalDoc Connect</span>
        </div>
        <div class="flex items-center gap-4">
            <a href="<?php echo $dashboardLink; ?>" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 font-semibold"><?php echo $backLabel; ?></a>
            <span class="text-sm text-slate-600">👋 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="?logout=1" class="px-4 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 font-semibold">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-6 py-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-slate-900 mb-2">My Profile</h1>
            <p class="text-slate-600">Manage your account settings</p>
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
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Profile Settings (Limited) -->
            <div class="bg-white rounded-2xl shadow-md p-6">
                <div class="flex items-center gap-2 mb-6">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <h2 class="text-2xl font-bold">Profile Information</h2>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 mb-4">
                    <p class="text-sm text-blue-800">ℹ️ You can update your name and phone number. Contact admin to change email.</p>
                </div>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email (Read-only)</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 text-slate-500 cursor-not-allowed">
                        <p class="text-xs text-slate-500 mt-1">Email cannot be changed. Contact admin if needed.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="+94 77 000 0000">
                    </div>

                    <button type="submit" name="update_profile"
                        class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition-all">
                        Update Profile
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-2xl shadow-md p-6">
                <div class="flex items-center gap-2 mb-6">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <h2 class="text-2xl font-bold">Change Password</h2>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 mb-4">
                    <p class="text-sm text-green-800">🔒 Use a strong password with at least 8 characters</p>
                </div>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Current Password</label>
                        <input type="password" name="current_password" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                            placeholder="Enter current password">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">New Password</label>
                        <input type="password" name="new_password" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                            placeholder="Min. 8 characters">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                            placeholder="Re-enter new password">
                    </div>

                    <button type="submit" name="change_password"
                        class="w-full bg-green-600 text-white font-bold py-3 rounded-xl hover:bg-green-700 transition-all">
                        Change Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Account Information -->
        <div class="bg-white rounded-2xl shadow-md p-6">
            <h2 class="text-2xl font-bold mb-4">Account Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-slate-50 rounded-xl p-4">
                    <div class="text-sm text-slate-500 mb-1">Account Type</div>
                    <div class="font-bold text-slate-900 uppercase"><?php echo htmlspecialchars($user['role']); ?></div>
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <div class="text-sm text-slate-500 mb-1">Status</div>
                    <div class="font-bold <?php echo $user['is_active'] ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                    </div>
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <div class="text-sm text-slate-500 mb-1">Member Since</div>
                    <div class="font-bold text-slate-900"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
