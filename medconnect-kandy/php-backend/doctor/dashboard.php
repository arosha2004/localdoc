<?php
session_start();
require_once '../config/database.php';

// Check doctor authentication
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
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

// Get doctor's assigned patients (bookings)
$stmt = $db->prepare("
    SELECT b.id, b.status, b.appointment_date, b.notes,
           u.id as patient_id, u.full_name as patient_name, u.email as patient_email, u.phone as patient_phone,
           p.diagnosis, p.prescription, p.created_at as prescription_date
    FROM bookings b
    JOIN users u ON b.patient_id = u.id
    LEFT JOIN prescriptions p ON b.id = p.booking_id
    WHERE b.doctor_id = ?
    ORDER BY b.appointment_date DESC
");
$stmt->execute([$user['id']]);
$patients = $stmt->fetchAll();

// Handle prescription update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_prescription'])) {
    $booking_id = $_POST['booking_id'];
    $diagnosis = $_POST['diagnosis'];
    $prescription = $_POST['prescription'];
    $notes = $_POST['notes'] ?? '';
    
    // Check if prescription exists
    $stmt = $db->prepare("SELECT id FROM prescriptions WHERE booking_id = ? AND doctor_id = ?");
    $stmt->execute([$booking_id, $user['id']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing
        $stmt = $db->prepare("UPDATE prescriptions SET diagnosis = ?, prescription = ?, notes = ? WHERE booking_id = ?");
        $stmt->execute([$diagnosis, $prescription, $notes, $booking_id]);
    } else {
        // Get patient_id from booking
        $stmt = $db->prepare("SELECT patient_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        
        // Create new prescription
        $stmt = $db->prepare("INSERT INTO prescriptions (booking_id, doctor_id, patient_id, diagnosis, prescription, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$booking_id, $user['id'], $booking['patient_id'], $diagnosis, $prescription, $notes]);
    }
    
    header('Location: dashboard.php?success=1');
    exit;
}

$success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - LocalDoc Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-green-700 text-white px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center">
                <svg viewBox="0 0 32 32" fill="none" class="w-6 h-6">
                    <path d="M16 8V24M8 16H24" stroke="#16a34a" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>
            <span class="text-xl font-bold">Doctor Portal</span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm">👨‍⚕️ <?php echo htmlspecialchars($user['full_name']); ?></span>
            <?php if ($user['specialization']): ?>
                <span class="px-3 py-1 bg-green-600 rounded-lg text-xs font-bold"><?php echo htmlspecialchars($user['specialization']); ?></span>
            <?php endif; ?>
            <a href="?logout=1" class="px-4 py-2 bg-red-600 rounded-xl hover:bg-red-700 font-semibold">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-slate-900 mb-2">My Patients</h1>
            <p class="text-slate-600">Manage appointments, diagnosis, and prescriptions</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                ✅ Prescription updated successfully!
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-md">
                <div class="text-sm text-slate-500 mb-1">Total Patients</div>
                <div class="text-3xl font-bold text-slate-900"><?php echo count($patients); ?></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-md">
                <div class="text-sm text-slate-500 mb-1">Confirmed Appointments</div>
                <div class="text-3xl font-bold text-green-600">
                    <?php echo count(array_filter($patients, fn($p) => $p['status'] === 'confirmed')); ?>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-md">
                <div class="text-sm text-slate-500 mb-1">Pending</div>
                <div class="text-3xl font-bold text-orange-600">
                    <?php echo count(array_filter($patients, fn($p) => $p['status'] === 'pending')); ?>
                </div>
            </div>
        </div>

        <!-- Patients List -->
        <div class="bg-white rounded-2xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-green-50 border-b border-green-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-green-700 uppercase">Patient</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-green-700 uppercase">Contact</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-green-700 uppercase">Appointment</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-green-700 uppercase">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-green-700 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($patients as $patient): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($patient['patient_name']); ?></div>
                                <div class="text-sm text-slate-500">ID: #<?php echo $patient['patient_id']; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-slate-600"><?php echo htmlspecialchars($patient['patient_email']); ?></div>
                                <div class="text-sm text-slate-600"><?php echo htmlspecialchars($patient['patient_phone'] ?: 'N/A'); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <?php echo $patient['appointment_date'] ? date('M d, Y H:i', strtotime($patient['appointment_date'])) : 'Not scheduled'; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 <?php 
                                    echo $patient['status'] === 'confirmed' ? 'bg-green-100 text-green-700' : 
                                        ($patient['status'] === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-700'); 
                                ?> rounded-lg text-xs font-bold">
                                    <?php echo ucfirst($patient['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="openPrescriptionModal(<?php echo htmlspecialchars(json_encode($patient)); ?>)" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 font-semibold text-sm">
                                    <?php echo $patient['prescription'] ? 'Update Prescription' : 'Add Prescription'; ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Prescription Modal -->
    <div id="prescriptionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold">Prescription Form</h3>
                <button onclick="closePrescriptionModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="booking_id" id="booking_id">
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Patient Name</label>
                    <input type="text" id="patient_name_display" readonly 
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Diagnosis *</label>
                    <textarea name="diagnosis" id="diagnosis" required rows="3"
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                        placeholder="Enter diagnosis..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Prescription *</label>
                    <textarea name="prescription" id="prescription" required rows="5"
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                        placeholder="Enter prescription details..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Additional Notes</label>
                    <textarea name="notes" id="notes" rows="2"
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-600"
                        placeholder="Any additional notes..."></textarea>
                </div>

                <button type="submit" name="update_prescription" 
                    class="w-full bg-green-600 text-white font-bold py-3 rounded-xl hover:bg-green-700 transition-all">
                    Save Prescription
                </button>
            </form>
        </div>
    </div>

    <script>
        function openPrescriptionModal(patient) {
            document.getElementById('booking_id').value = patient.id;
            document.getElementById('patient_name_display').value = patient.patient_name;
            document.getElementById('diagnosis').value = patient.diagnosis || '';
            document.getElementById('prescription').value = patient.prescription || '';
            document.getElementById('notes').value = patient.notes || '';
            document.getElementById('prescriptionModal').classList.remove('hidden');
        }

        function closePrescriptionModal() {
            document.getElementById('prescriptionModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.id === 'prescriptionModal') {
                closePrescriptionModal();
            }
        });
    </script>
</body>
</html>
