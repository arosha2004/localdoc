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

// Get patient's tokens
$stmt = $db->prepare("
    SELECT ot.*, os.opd_name, os.session_date, os.start_time, os.end_time,
           mc.name as clinic_name, mc.area, mc.phone as clinic_phone
    FROM opd_tokens ot
    JOIN opd_sessions os ON ot.session_id = os.id
    JOIN medical_centers mc ON os.clinic_id = mc.id
    WHERE ot.patient_id = ?
    ORDER BY os.session_date DESC, ot.created_at DESC
");
$stmt->execute([$user['id']]);
$tokens = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tokens - LocalDoc Connect</title>
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
            <a href="dashboard.php" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 font-semibold">Back</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-6 py-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-slate-900 mb-2">My OPD Tokens</h1>
            <p class="text-slate-600">View your token history and upcoming appointments</p>
        </div>

        <?php if (empty($tokens)): ?>
        <div class="bg-white rounded-2xl shadow-md p-12 text-center">
            <div class="text-6xl mb-4">🎫</div>
            <h3 class="text-2xl font-bold text-slate-900 mb-2">No Tokens Yet</h3>
            <p class="text-slate-600 mb-6">You haven't booked any OPD tokens yet</p>
            <a href="opd-book.php" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold">
                Book Your First Token
            </a>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($tokens as $token): 
                $statusColors = [
                    'pending' => 'bg-gray-100 text-gray-700',
                    'waiting' => 'bg-blue-100 text-blue-700',
                    'called' => 'bg-green-100 text-green-700',
                    'served' => 'bg-purple-100 text-purple-700',
                    'cancelled' => 'bg-red-100 text-red-700',
                    'no-show' => 'bg-orange-100 text-orange-700'
                ];
                $isUpcoming = strtotime($token['session_date']) >= time();
            ?>
            <div class="bg-white rounded-2xl shadow-md p-6 <?php echo $isUpcoming ? 'border-l-4 border-blue-600' : ''; ?>">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="text-3xl font-bold text-blue-600 mb-1"><?php echo htmlspecialchars($token['token_number']); ?></div>
                        <div class="text-sm text-slate-500"><?php echo htmlspecialchars($token['clinic_name']); ?> - <?php echo htmlspecialchars($token['area']); ?></div>
                    </div>
                    <span class="px-3 py-1 <?php echo $statusColors[$token['status']]; ?> rounded-lg text-xs font-bold uppercase">
                        <?php echo htmlspecialchars($token['status']); ?>
                    </span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                    <div>
                        <div class="text-slate-500 mb-1">OPD</div>
                        <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($token['opd_name']); ?></div>
                    </div>
                    <div>
                        <div class="text-slate-500 mb-1">Date</div>
                        <div class="font-semibold text-slate-900"><?php echo date('M d, Y', strtotime($token['session_date'])); ?></div>
                    </div>
                    <div>
                        <div class="text-slate-500 mb-1">Time</div>
                        <div class="font-semibold text-slate-900"><?php echo date('g:i A', strtotime($token['start_time'])); ?></div>
                    </div>
                    <div>
                        <div class="text-slate-500 mb-1">Est. Wait</div>
                        <div class="font-semibold text-orange-600"><?php echo $token['estimated_time']; ?> min</div>
                    </div>
                </div>

                <?php if ($token['token_type'] === 'walk-in'): ?>
                <div class="bg-orange-50 border border-orange-200 rounded-lg px-4 py-2 text-sm text-orange-800">
                    🏥 Walk-in Token (Registered at Hospital)
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
