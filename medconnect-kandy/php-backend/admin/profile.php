<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/security.php';
require_once '../helpers/functions.php';

// Check admin authentication
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$user = $_SESSION['user'];
$db = getDBConnection();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    if (empty($full_name) || empty($email)) {
        $error = 'Name and email are required';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email format';
    } else {
        try {
            // Check if email is taken by another user
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $error = 'Email already in use';
            } else {
                $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $user['id']]);
                
                // Update session
                $_SESSION['user']['full_name'] = $full_name;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['phone'] = $phone;
                
                $success = 'Profile updated successfully!';
            }
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

// Handle user management (deactivate/reactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manage_user'])) {
    $target_user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    try {
        if ($action === 'deactivate') {
            $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND id != ?");
        } elseif ($action === 'activate') {
            $stmt = $db->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND id != ?");
        }
        $stmt->execute([$target_user_id, $user['id']]);
        $success = 'User updated successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to update user';
    }
}

// Get all users for management
$stmt = $db->query("SELECT id, full_name, email, phone, role, is_active, is_verified, created_at FROM users ORDER BY created_at DESC");
$all_users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - LocalDoc Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-slate-900 text-white px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center">
                <svg viewBox="0 0 32 32" fill="none" class="w-6 h-6">
                    <path d="M16 8V24M8 16H24" stroke="#1e293b" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>
            <span class="text-xl font-bold">Admin Portal</span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm">🔐 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="dashboard.php" class="px-4 py-2 bg-slate-700 rounded-xl hover:bg-slate-600 font-semibold">Dashboard</a>
            <a href="?logout=1" class="px-4 py-2 bg-red-600 rounded-xl hover:bg-red-700 font-semibold">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-6 py-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-slate-900 mb-2">Admin Settings</h1>
            <p class="text-slate-600">Manage your profile and system users</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Profile Settings -->
            <div class="bg-white rounded-2xl shadow-md p-6">
                <h2 class="text-2xl font-bold mb-6">Profile Settings</h2>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Phone</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>

                    <button type="submit" name="update_profile"
                        class="w-full bg-slate-900 text-white font-bold py-3 rounded-xl hover:bg-slate-800 transition-all">
                        Update Profile
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-2xl shadow-md p-6">
                <h2 class="text-2xl font-bold mb-6">Change Password</h2>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Current Password</label>
                        <input type="password" name="current_password" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">New Password</label>
                        <input type="password" name="new_password" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>

                    <button type="submit" name="change_password"
                        class="w-full bg-slate-900 text-white font-bold py-3 rounded-xl hover:bg-slate-800 transition-all">
                        Change Password
                    </button>
                </form>
            </div>
        </div>

        <!-- User Management -->
        <div class="bg-white rounded-2xl shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-2xl font-bold">Manage Users</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">User</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Role</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Registered</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($all_users as $u): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                <div class="text-sm text-slate-500"><?php echo htmlspecialchars($u['email']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 <?php echo $u['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : ($u['role'] === 'doctor' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'); ?> rounded-lg text-xs font-bold">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 <?php echo $u['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'; ?> rounded-lg text-xs font-bold">
                                    <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td class="px-6 py-4">
                                <?php if ($u['id'] != $user['id']): ?>
                                <form method="POST" class="inline-flex gap-2">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <?php if ($u['is_active']): ?>
                                        <button type="submit" name="manage_user" value="deactivate" class="px-3 py-1 bg-orange-100 text-orange-700 rounded-lg text-xs font-bold hover:bg-orange-200">Deactivate</button>
                                    <?php else: ?>
                                        <button type="submit" name="manage_user" value="activate" class="px-3 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-bold hover:bg-green-200">Activate</button>
                                    <?php endif; ?>
                                    <button type="submit" name="manage_user" value="delete" class="px-3 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-bold hover:bg-red-200" onclick="return confirm('Delete this user permanently?')">Delete</button>
                                </form>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
