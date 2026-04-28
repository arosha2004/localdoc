<?php
session_start();
require_once '../config/database.php';

// Check admin authentication
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$user = $_SESSION['user'];

$db = getDBConnection();

// Get clinics
$stmt = $db->query("SELECT * FROM medical_centers ORDER BY id");
$clinics = $stmt->fetchAll();

// Get pending doctors
$stmt = $db->query("SELECT id, full_name, email, phone, specialization, slmc_registration, nic_number, hospital_name, verification_document, created_at FROM users WHERE role = 'doctor' AND is_active = 0 ORDER BY created_at DESC");
$pending_doctors = $stmt->fetchAll();

// Get all doctors
$stmt = $db->query("SELECT id, full_name, email, phone, specialization, is_active, is_verified, created_at FROM users WHERE role = 'doctor' ORDER BY created_at DESC");
$all_doctors = $stmt->fetchAll();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

// Handle AJAX toggle request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_clinic'])) {
    header('Content-Type: application/json');
    $clinic_id = intval($_POST['clinic_id']);
    $stmt = $db->prepare("UPDATE medical_centers SET doctor_available = NOT doctor_available WHERE id = ?");
    $stmt->execute([$clinic_id]);
    echo json_encode(['success' => true]);
    exit;
}

// Handle doctor approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_doctor'])) {
    header('Content-Type: application/json');
    $doctor_id = intval($_POST['doctor_id']);
    $action = $_POST['action']; // 'approve' or 'reject'
    
    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE users SET is_active = 1, is_verified = 1 WHERE id = ? AND role = 'doctor'");
    } else {
        $stmt = $db->prepare("UPDATE users SET is_active = 0, is_verified = 0 WHERE id = ? AND role = 'doctor'");
    }
    $stmt->execute([$doctor_id]);
    echo json_encode(['success' => true]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LocalDoc Connect</title>
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
            <span class="px-3 py-1 bg-blue-600 rounded-lg text-xs font-bold">Admin</span>
            <a href="?logout=1" class="px-4 py-2 bg-red-600 rounded-xl hover:bg-red-700 font-semibold">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-slate-900 mb-2">Admin Dashboard</h1>
            <p class="text-slate-600">Manage doctors, clinics, and system</p>
        </div>

        <!-- Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <ul class="flex flex-wrap -mb-px">
                <li class="mr-2">
                    <button onclick="showTab('clinics')" id="tab-clinics" class="tab-btn inline-block p-4 rounded-t-lg border-b-2 border-blue-600 text-blue-600 font-bold">
                        Clinics
                    </button>
                </li>
                <li class="mr-2">
                    <button onclick="showTab('pending-doctors')" id="tab-pending-doctors" class="tab-btn inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:border-gray-300 font-bold">
                        Pending Doctors <?php if (count($pending_doctors) > 0): ?><span class="ml-2 px-2 py-1 bg-orange-500 text-white text-xs rounded-full"><?php echo count($pending_doctors); ?></span><?php endif; ?>
                    </button>
                </li>
                <li class="mr-2">
                    <button onclick="showTab('all-doctors')" id="tab-all-doctors" class="tab-btn inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:border-gray-300 font-bold">
                        All Doctors
                    </button>
                </li>
            </ul>
        </div>

        <!-- Clinics Tab -->
        <div id="content-clinics" class="tab-content">

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-md">
                <div class="text-sm text-slate-500 mb-1">Total Clinics</div>
                <div class="text-3xl font-bold text-slate-900"><?php echo count($clinics); ?></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-md">
                <div class="text-sm text-slate-500 mb-1">Doctors Available</div>
                <div class="text-3xl font-bold text-green-600">
                    <?php echo count(array_filter($clinics, fn($c) => $c['doctor_available'])); ?>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-md">
                <div class="text-sm text-slate-500 mb-1">Doctors Unavailable</div>
                <div class="text-3xl font-bold text-red-600">
                    <?php echo count(array_filter($clinics, fn($c) => !$c['doctor_available'])); ?>
                </div>
            </div>
        </div>

        <!-- Clinics List -->
        <div class="bg-white rounded-2xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Clinic Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Area</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Type</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($clinics as $clinic): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($clinic['name']); ?></div>
                                <div class="text-sm text-slate-500"><?php echo htmlspecialchars($clinic['phone']); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($clinic['area']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 <?php echo $clinic['tag'] === 'private' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?> rounded-lg text-xs font-bold">
                                    <?php echo ucfirst($clinic['tag']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full <?php echo $clinic['doctor_available'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                                    <span class="text-sm font-semibold <?php echo $clinic['doctor_available'] ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $clinic['doctor_available'] ? 'Available' : 'Not Available'; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="toggleAvailability(<?php echo $clinic['id']; ?>)" 
                                    class="toggle-btn px-4 py-2 <?php echo $clinic['doctor_available'] ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-green-50 text-green-600 hover:bg-green-100'; ?> rounded-xl font-semibold text-sm transition-all"
                                    data-id="<?php echo $clinic['id']; ?>">
                                    <?php echo $clinic['doctor_available'] ? 'Mark Unavailable' : 'Mark Available'; ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>

        <!-- Pending Doctors Tab -->
        <div id="content-pending-doctors" class="tab-content hidden">
            <?php if (count($pending_doctors) > 0): ?>
            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-orange-50 border-b border-orange-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-orange-700 uppercase">Doctor</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-orange-700 uppercase">SLMC Reg#</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-orange-700 uppercase">Hospital</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-orange-700 uppercase">Document</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-orange-700 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($pending_doctors as $doctor): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($doctor['full_name']); ?></div>
                                    <div class="text-sm text-slate-500"><?php echo htmlspecialchars($doctor['email']); ?></div>
                                    <div class="text-sm text-slate-500"><?php echo htmlspecialchars($doctor['phone']); ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($doctor['slmc_registration']); ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($doctor['hospital_name']); ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($doctor['verification_document']): ?>
                                        <a href="../<?php echo htmlspecialchars($doctor['verification_document']); ?>" target="_blank" class="text-blue-600 hover:underline text-sm font-semibold">View Document →</a>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-sm">No document</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <button onclick="approveDoctor(<?php echo $doctor['id']; ?>, 'approve')" class="px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 font-semibold text-sm">Approve</button>
                                        <button onclick="approveDoctor(<?php echo $doctor['id']; ?>, 'reject')" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 font-semibold text-sm">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-2xl shadow-md p-12 text-center">
                <div class="text-6xl mb-4">✅</div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">No Pending Doctors</h3>
                <p class="text-slate-600">All doctor registrations have been reviewed</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- All Doctors Tab -->
        <div id="content-all-doctors" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-green-50 border-b border-green-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-green-700 uppercase">Doctor</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-green-700 uppercase">Specialization</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-green-700 uppercase">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-green-700 uppercase">Registered</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($all_doctors as $doctor): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($doctor['full_name']); ?></div>
                                    <div class="text-sm text-slate-500"><?php echo htmlspecialchars($doctor['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($doctor['specialization'] ?: 'N/A'); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 <?php echo $doctor['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'; ?> rounded-lg text-xs font-bold">
                                        <?php echo $doctor['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?php echo date('M d, Y', strtotime($doctor['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Tab switching
        function showTab(tabName) {
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('border-blue-600', 'text-blue-600');
                el.classList.add('border-transparent');
            });
            
            // Show selected
            document.getElementById('content-' + tabName).classList.remove('hidden');
            document.getElementById('tab-' + tabName).classList.add('border-blue-600', 'text-blue-600');
            document.getElementById('tab-' + tabName).classList.remove('border-transparent');
        }
        
        function toggleAvailability(clinicId) {
            const formData = new FormData();
            formData.append('toggle_clinic', '1');
            formData.append('clinic_id', clinicId);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        function approveDoctor(doctorId, action) {
            if (!confirm('Are you sure you want to ' + action + ' this doctor?')) return;
            
            const formData = new FormData();
            formData.append('approve_doctor', '1');
            formData.append('doctor_id', doctorId);
            formData.append('action', action);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Doctor ' + (action === 'approve' ? 'approved' : 'rejected') + ' successfully!');
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>
