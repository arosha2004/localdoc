<?php
session_start();
require_once 'config/database.php';

// Check authentication
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$db = getDBConnection();

// Get clinics with OPD sessions
$stmt = $db->query("
    SELECT mc.*, 
           GROUP_CONCAT(DISTINCT os.opd_name) as opd_names,
           COUNT(DISTINCT os.id) as session_count
    FROM medical_centers mc
    JOIN opd_sessions os ON mc.id = os.clinic_id
    WHERE os.session_date >= CURDATE() AND os.is_active = 1
    GROUP BY mc.id
    ORDER BY mc.name
");
$clinics = $stmt->fetchAll();

// Handle token booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_token'])) {
    $session_id = intval($_POST['session_id']);
    $token_type = 'online';
    
    // Get session details
    $stmt = $db->prepare("
        SELECT os.*, mc.name as clinic_name, mc.area
        FROM opd_sessions os
        JOIN medical_centers mc ON os.clinic_id = mc.id
        WHERE os.id = ? AND os.is_active = 1
    ");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch();
    
    if (!$session) {
        $error = 'Invalid session selected';
    } elseif ($session['current_token'] >= $session['max_tokens']) {
        $error = 'All tokens for this session have been booked';
    } else {
        // Check for double booking - patient already has token for this session
        $stmt = $db->prepare("
            SELECT id FROM opd_tokens 
            WHERE patient_id = ? AND session_id = ? AND status NOT IN ('cancelled', 'no-show')
        ");
        $stmt->execute([$user['id'], $session_id]);
        $existingToken = $stmt->fetch();
        
        if ($existingToken) {
            $error = 'You already have a token for this session';
        } else {
        try {
            $db->beginTransaction();
            
            // Increment current token
            $stmt = $db->prepare("UPDATE opd_sessions SET current_token = current_token + 1 WHERE id = ?");
            $stmt->execute([$session_id]);
            
            // Generate token number
            $token_number = 'OPD-' . str_pad($session['current_token'] + 1, 3, '0', STR_PAD_LEFT);
            
            // Calculate estimated waiting time (15 min per token before this one)
            $estimated_wait_minutes = $session['current_token'] * 15;
            
            // Calculate estimated appointment time
            $session_start_datetime = $session['session_date'] . ' ' . $session['start_time'];
            $estimated_datetime = strtotime($session_start_datetime) + ($estimated_wait_minutes * 60);
            $estimated_date = date('Y-m-d', $estimated_datetime);
            $estimated_time_of_day = date('H:i:s', $estimated_datetime);
            $estimated_wait_display = $estimated_wait_minutes;
            
            // Create token
            $stmt = $db->prepare("
                INSERT INTO opd_tokens (token_number, session_id, patient_id, token_type, status, estimated_time)
                VALUES (?, ?, ?, ?, 'waiting', ?)
            ");
            $stmt->execute([
                $token_number,
                $session_id,
                $user['id'],
                $token_type,
                $estimated_wait_display
            ]);
            
            $db->commit();
            
            // Get created token
            $stmt = $db->prepare("
                SELECT ot.*, os.opd_name, os.session_date, os.start_time, os.end_time, mc.name as clinic_name, mc.area
                FROM opd_tokens ot
                JOIN opd_sessions os ON ot.session_id = os.id
                JOIN medical_centers mc ON os.clinic_id = mc.id
                WHERE ot.id = ?
            ");
            $stmt->execute([$db->lastInsertId()]);
            $token = $stmt->fetch();
            
            // Calculate estimated appointment datetime for display
            $session_start_datetime = $token['session_date'] . ' ' . $token['start_time'];
            $estimated_datetime = strtotime($session_start_datetime) + ($token['estimated_time'] * 60);
            $token['estimated_date'] = date('Y-m-d', $estimated_datetime);
            $token['estimated_time_display'] = date('g:i A', $estimated_datetime);
            $token['session_start_formatted'] = date('g:i A', strtotime($token['start_time']));
            
            $success = $token;
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Failed to book token. Please try again.';
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
    <title>OPD Token - LocalDoc Connect</title>
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
            <span class="text-sm text-slate-600">👋 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="opd-tokens.php" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 font-semibold">View OPD Tokens</a>
            <a href="dashboard.php" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 font-semibold">Back to Dashboard</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-6 py-8">
        <?php if (isset($success) && $success): ?>
        <!-- Success - Token Generated -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Token Generated Successfully!</h1>
                <p class="text-slate-600">Your OPD token has been booked</p>
            </div>

            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 mb-6">
                <div class="text-center mb-6">
                    <div class="text-sm text-slate-600 mb-2">Your Token Number</div>
                    <div class="text-5xl font-bold text-blue-600"><?php echo htmlspecialchars($success['token_number']); ?></div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-slate-500 mb-1">Hospital</div>
                        <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($success['clinic_name']); ?></div>
                    </div>
                    <div>
                        <div class="text-slate-500 mb-1">OPD</div>
                        <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($success['opd_name']); ?></div>
                    </div>
                    <div>
                        <div class="text-slate-500 mb-1">Session Date</div>
                        <div class="font-semibold text-slate-900"><?php echo date('M d, Y', strtotime($success['session_date'])); ?></div>
                    </div>
                    <div>
                        <div class="text-slate-500 mb-1">Session Start Time</div>
                        <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($success['session_start_formatted']); ?></div>
                    </div>
                    <div>
                        <div class="text-slate-500 mb-1">Your Estimated Time</div>
                        <div class="font-semibold text-green-600"><?php echo htmlspecialchars($success['estimated_time_display']); ?></div>
                    </div>
                    <div>
                        <div class="text-slate-500 mb-1">Estimated Wait</div>
                        <div class="font-semibold text-orange-600"><?php echo $success['estimated_time']; ?> minutes</div>
                    </div>
                    <div>
                        <div class="text-slate-500 mb-1">Token Number</div>
                        <div class="font-semibold text-blue-600"><?php echo htmlspecialchars($success['token_number']); ?></div>
                    </div>
                    <div>
                        <div class="text-slate-500 mb-1">Token Type</div>
                        <div class="font-semibold text-slate-900 uppercase"><?php echo htmlspecialchars($success['token_type']); ?></div>
                    </div>
                </div>
            </div>

            <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-6">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">ℹ️</span>
                    <div class="text-sm text-orange-800">
                        <p class="font-bold mb-1">Important:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Please arrive 15 minutes before your estimated time</li>
                            <li>Bring your NIC and any medical reports</li>
                            <li>Token is valid only for the selected date</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="flex gap-4">
                <a href="opd-tokens.php" class="flex-1 text-center px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold">
                    View My Tokens
                </a>
                <a href="opd-book.php" class="flex-1 text-center px-6 py-3 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 font-semibold">
                    Book Another Token
                </a>
            </div>
        </div>

        <?php else: ?>
        <!-- Booking Form -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-slate-900 mb-2">Book OPD Token</h1>
            <p class="text-slate-600">Select hospital, OPD, and date to get your token</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-white rounded-2xl shadow-md p-6 space-y-6">
            <!-- Hospital Selection -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Select Hospital *</label>
                <select id="clinicSelect" required class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choose a hospital...</option>
                    <?php foreach ($clinics as $clinic): ?>
                        <option value="<?php echo $clinic['id']; ?>" data-opds="<?php echo htmlspecialchars($clinic['opd_names']); ?>">
                            <?php echo htmlspecialchars($clinic['name']); ?> - <?php echo htmlspecialchars($clinic['area']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- OPD Selection -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Select OPD *</label>
                <select name="session_id" id="opdSelect" required class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select hospital first...</option>
                </select>
            </div>

            <!-- Date Selection -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Date *</label>
                <input type="date" name="session_date" id="dateSelect" required min="<?php echo date('Y-m-d'); ?>" 
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" name="book_token" 
                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold py-3 rounded-xl hover:shadow-lg transition-all">
                Generate Token
            </button>
        </form>
        <?php endif; ?>
    </main>

    <script>
        // OPD sessions data from server
        const opdSessions = <?php
            $stmt = $db->query("
                SELECT os.id, os.clinic_id, os.opd_name, os.session_date, os.start_time, os.end_time,
                       os.max_tokens, os.current_token, mc.name as clinic_name
                FROM opd_sessions os
                JOIN medical_centers mc ON os.clinic_id = mc.id
                WHERE os.session_date >= CURDATE() AND os.is_active = 1
                ORDER BY os.session_date, os.start_time
            ");
            $sessions = $stmt->fetchAll();
            echo json_encode($sessions);
        ?>;

        // Filter OPDs when hospital is selected
        document.getElementById('clinicSelect').addEventListener('change', function() {
            const clinicId = this.value;
            const opdSelect = document.getElementById('opdSelect');
            const dateSelect = document.getElementById('dateSelect');
            
            // Clear OPD dropdown
            opdSelect.innerHTML = '<option value="">Choose an OPD...</option>';
            
            if (!clinicId) return;
            
            // Filter and add OPDs
            const filteredSessions = opdSessions.filter(s => s.clinic_id == clinicId);
            const uniqueOPDs = [...new Set(filteredSessions.map(s => s.opd_name))];
            
            uniqueOPDs.forEach(opdName => {
                const session = filteredSessions.find(s => s.opd_name === opdName);
                const available = session.max_tokens - session.current_token;
                const option = document.createElement('option');
                option.value = session.id;
                option.textContent = `${opdName} (${available} tokens available)`;
                option.dataset.sessionId = session.id;
                opdSelect.appendChild(option);
            });
        });
    </script>
</body>
</html>
