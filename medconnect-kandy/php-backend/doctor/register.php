<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/security.php';
require_once '../helpers/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'doctor') {
        header('Location: dashboard.php');
    } else {
        header('Location: ../dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $specialization = sanitizeInput($_POST['specialization'] ?? '');
    $slmc_registration = sanitizeInput($_POST['slmc_registration'] ?? '');
    $nic_number = sanitizeInput($_POST['nic_number'] ?? '');
    $hospital_name = sanitizeInput($_POST['hospital_name'] ?? '');
    
    // File upload handling
    $verification_document = null;
    if (isset($_FILES['verification_document']) && $_FILES['verification_document']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/verifications/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['verification_document']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            $error = 'Only PDF, JPG, and PNG files are allowed';
        } else {
            $filename = 'doc_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['verification_document']['tmp_name'], $upload_path)) {
                $verification_document = 'uploads/verifications/' . $filename;
            } else {
                $error = 'Failed to upload document';
            }
        }
    }
    
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'All required fields must be filled';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (empty($slmc_registration)) {
        $error = 'SLMC Registration Number is required';
    } elseif (empty($nic_number)) {
        $error = 'NIC Number is required';
    } elseif (empty($hospital_name)) {
        $error = 'Hospital Name is required';
    } elseif (!$verification_document) {
        $error = 'Verification document is required';
    } else {
        try {
            $db = getDBConnection();
            
            // Check if email exists
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
                    // Check if SLMC registration exists
                    $stmt = $db->prepare("SELECT id FROM users WHERE slmc_registration = ?");
                    $stmt->execute([$slmc_registration]);
                    if ($stmt->fetch()) {
                        $error = 'This SLMC Registration Number is already registered';
                    } else {
                        // Create doctor account (pending status)
                        $hashedPassword = hashPassword($password);
                        $stmt = $db->prepare("
                            INSERT INTO users (full_name, email, phone, hashed_password, role, specialization, 
                                             slmc_registration, nic_number, hospital_name, verification_document,
                                             is_active, is_verified, created_at)
                            VALUES (?, ?, ?, ?, 'doctor', ?, ?, ?, ?, ?, 0, 0, NOW())
                        ");
                        $stmt->execute([
                            $full_name, $email, $phone, $hashedPassword,
                            $specialization, $slmc_registration, $nic_number, $hospital_name, $verification_document
                        ]);
                        
                        $success = true;
                    }
                }
            } else {
                $error = 'Phone number is required for doctor registration';
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration - LocalDoc Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-teal-100 min-h-screen flex items-center justify-center py-12">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-2xl">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-green-600 to-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg viewBox="0 0 32 32" fill="none" class="w-10 h-10">
                    <path d="M16 8V24M8 16H24" stroke="white" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900">Doctor Registration</h1>
            <p class="text-slate-500 mt-2">Join our medical network in Kandy</p>
            <div class="mt-4 bg-orange-50 border border-orange-200 text-orange-700 px-4 py-3 rounded-xl text-sm">
                ⚠️ Your account will be reviewed by admin before activation
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-6 rounded-xl mb-6">
                <div class="text-lg font-bold mb-2">✅ Registration Successful!</div>
                <p class="text-sm">Your account is pending admin verification. You will receive an email once your account is approved.</p>
                <a href="../index.php" class="inline-block mt-4 text-green-700 font-semibold hover:underline">← Back to Login</a>
            </div>
        <?php else: ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Full Name *</label>
                    <input type="text" name="full_name" required 
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                        placeholder="Dr. Kamal Perera">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address *</label>
                    <input type="email" name="email" required 
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                        placeholder="doctor@example.com">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Phone Number *</label>
                    <input type="tel" name="phone" required 
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                        placeholder="+94 77 000 0000">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Specialization</label>
                    <input type="text" name="specialization" 
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                        placeholder="General Physician">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">SLMC Registration Number *</label>
                    <input type="text" name="slmc_registration" required 
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                        placeholder="SLMC/12345">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">NIC Number *</label>
                    <input type="text" name="nic_number" required 
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                        placeholder="123456789V">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Hospital Name *</label>
                <input type="text" name="hospital_name" required 
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                    placeholder="Kandy General Hospital">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Verification Document (SLMC ID / Appointment Letter) *</label>
                <input type="file" name="verification_document" required accept=".pdf,.jpg,.jpeg,.png"
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600">
                <p class="text-xs text-slate-500 mt-1">Accepted: PDF, JPG, PNG (Max 5MB)</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Password *</label>
                    <input type="password" name="password" required 
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                        placeholder="Min. 8 characters">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Confirm Password *</label>
                    <input type="password" name="confirm_password" required 
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                        placeholder="Re-enter password">
                </div>
            </div>

            <button type="submit" 
                class="w-full bg-gradient-to-r from-green-600 to-teal-600 text-white font-bold py-3 rounded-xl hover:shadow-lg transition-all mt-6">
                Submit Registration for Review
            </button>
        </form>
        <?php endif; ?>

        <p class="text-center mt-6 text-slate-600">
            Already have an account? 
            <a href="../index.php" class="text-green-600 font-semibold hover:underline">Sign in</a>
        </p>
    </div>
</body>
</html>
