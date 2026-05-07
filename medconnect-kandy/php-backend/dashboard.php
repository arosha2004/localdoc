<?php
session_start();
require_once 'config/database.php';
require_once 'middleware/auth.php';

// Set security headers
setSecurityHeaders();

// Check authentication
$user = requireSessionAuth();

// Get clinics
$db = getDBConnection();
$stmt = $db->query("SELECT * FROM medical_centers ORDER BY id");
$clinics = $stmt->fetchAll();

// Handle logout
if (isset($_GET['logout'])) {
    // Clear session
    $_SESSION = [];
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LocalDoc Connect</title>
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
            <a href="nearby-hospitals.php" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold">📍 Nearby Hospitals</a>
            <a href="opd-book.php" class="px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 font-semibold">🎫 Book OPD Token</a>
            <a href="patient-profile.php" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 font-semibold">My Profile</a>
            <span class="text-sm text-slate-600">👋 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="?logout=1" class="px-4 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 font-semibold">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-slate-900 mb-2">Medical Centers</h1>
            <p class="text-slate-600">Find trusted healthcare facilities in Kandy</p>
        </div>

        <!-- Search -->
        <div class="mb-8">
            <input type="text" id="searchInput" placeholder="Search by name, area, or service..." 
                class="w-full px-6 py-4 border border-gray-200 rounded-2xl text-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <!-- Clinics Grid -->
        <div id="clinicsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($clinics as $clinic): 
                $services = json_decode($clinic['services'], true);
            ?>
            <div class="clinic-card bg-white rounded-2xl p-6 shadow-md hover:shadow-xl transition-all" 
                 data-name="<?php echo strtolower($clinic['name']); ?>" 
                 data-area="<?php echo strtolower($clinic['area']); ?>"
                 data-services="<?php echo strtolower(implode(' ', $services)); ?>">
                
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900"><?php echo htmlspecialchars($clinic['name']); ?></h3>
                        <p class="text-sm text-slate-500 mt-1"><?php echo htmlspecialchars($clinic['type']); ?></p>
                    </div>
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-bold">
                        <?php echo ucfirst($clinic['tag']); ?>
                    </span>
                </div>

                <div class="space-y-2 mb-4">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                        <?php echo htmlspecialchars($clinic['area']); ?>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        <?php echo htmlspecialchars($clinic['hours']); ?>
                    </div>
                </div>

                <p class="text-sm text-slate-500 mb-4"><?php echo htmlspecialchars($clinic['address']); ?></p>

                <!-- Doctor Availability -->
                <div class="mb-4 p-3 rounded-xl <?php echo $clinic['doctor_available'] ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full <?php echo $clinic['doctor_available'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                        <span class="text-sm font-semibold">
                            Doctor <?php echo $clinic['doctor_available'] ? 'Available' : 'Not Available'; ?>
                        </span>
                    </div>
                </div>

                <!-- Services -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <?php foreach (array_slice($services, 0, 4) as $service): ?>
                        <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-semibold">
                            <?php echo htmlspecialchars($service); ?>
                        </span>
                    <?php endforeach; ?>
                    <?php if (count($services) > 4): ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-semibold">
                            +<?php echo count($services) - 4; ?> more
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Footer -->
                <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                    <div class="flex items-center gap-1">
                        <span class="text-amber-400">★</span>
                        <span class="font-bold text-sm"><?php echo number_format($clinic['rating'], 1); ?></span>
                    </div>
                    <button onclick="showContactInfo(<?php echo $clinic['id']; ?>, '<?php echo htmlspecialchars($clinic['name']); ?>')" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-semibold">
                        View Contact Info
                    </button>
                </div>
                
                <!-- Contact Info Modal -->
                <div id="contactModal<?php echo $clinic['id']; ?>" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-2xl p-6 max-w-sm mx-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold"><?php echo htmlspecialchars($clinic['name']); ?></h3>
                            <button onclick="closeContactInfo(<?php echo $clinic['id']; ?>)" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                </svg>
                                <span class="text-lg font-semibold" id="phone<?php echo $clinic['id']; ?>"></span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                <span><?php echo htmlspecialchars($clinic['hours']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.clinic-card');
            
            cards.forEach(card => {
                const name = card.dataset.name || '';
                const area = card.dataset.area || '';
                const services = card.dataset.services || '';
                
                if (name.includes(query) || area.includes(query) || services.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Generate random phone number
        function generatePhoneNumber() {
            const prefixes = ['+94 81', '+94 77', '+94 71', '+94 70', '+94 76'];
            const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
            const number = Math.floor(Math.random() * 9000000 + 1000000);
            return prefix + ' ' + number;
        }
        
        // Show contact info modal
        function showContactInfo(clinicId, clinicName) {
            const phoneNumber = generatePhoneNumber();
            document.getElementById('phone' + clinicId).textContent = phoneNumber;
            document.getElementById('contactModal' + clinicId).classList.remove('hidden');
        }
        
        // Close contact info modal
        function closeContactInfo(clinicId) {
            document.getElementById('contactModal' + clinicId).classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('fixed')) {
                e.target.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
