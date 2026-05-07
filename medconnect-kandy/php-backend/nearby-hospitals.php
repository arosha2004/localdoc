<?php
session_start();
require_once 'config/database.php';

// Check authentication
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nearby Hospitals - LocalDoc Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
    <main class="max-w-7xl mx-auto px-6 py-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-slate-900 mb-2">📍 Nearby Hospitals</h1>
            <p class="text-slate-600">Find the closest medical centers to your location</p>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="bg-white rounded-2xl shadow-md p-12 text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mx-auto mb-4"></div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">Detecting Your Location...</h3>
            <p class="text-slate-600">Please allow location access to find nearby hospitals</p>
        </div>

        <!-- Error State -->
        <div id="errorState" class="hidden bg-white rounded-2xl shadow-md p-12 text-center">
            <div class="text-6xl mb-4">📍</div>
            <h3 class="text-2xl font-bold text-slate-900 mb-2">Location Access Required</h3>
            <p class="text-slate-600 mb-6">Please enable location services in your browser settings</p>
            <button onclick="getLocation()" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold">
                Try Again
            </button>
        </div>

        <!-- Content State -->
        <div id="contentState" class="hidden">
            <!-- Map -->
            <div class="bg-white rounded-2xl shadow-md p-4 mb-6">
                <div id="map" class="w-full rounded-xl" style="height: 400px;"></div>
            </div>

            <!-- User Location Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">📍</span>
                    <div>
                        <p class="font-bold text-blue-900 mb-1">Your Location</p>
                        <p class="text-sm text-blue-800" id="userLocationText">Detecting...</p>
                    </div>
                </div>
            </div>

            <!-- Hospitals List -->
            <div id="hospitalsList" class="space-y-4">
                <!-- Hospitals will be inserted here -->
            </div>
        </div>
    </main>

    <script>
        let userLat, userLng;
        let map, userMarker;
        let hospitals = [];

        // Haversine formula to calculate distance between two coordinates
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Earth's radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        // Get user location
        function getLocation() {
            document.getElementById('loadingState').classList.remove('hidden');
            document.getElementById('errorState').classList.add('hidden');
            document.getElementById('contentState').classList.add('hidden');

            if (!navigator.geolocation) {
                showError();
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userLat = position.coords.latitude;
                    userLng = position.coords.longitude;
                    document.getElementById('userLocationText').textContent = 
                        `Latitude: ${userLat.toFixed(6)}, Longitude: ${userLng.toFixed(6)}`;
                    loadHospitals();
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    showError();
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        // Load hospitals from API
        async function loadHospitals() {
            try {
                const response = await fetch('api/clinics.php');
                const clinics = await response.json();
                
                // Calculate distances and sort
                hospitals = clinics.map(clinic => {
                    const distance = calculateDistance(userLat, userLng, clinic.lat, clinic.lng);
                    return { ...clinic, distance: distance };
                }).sort((a, b) => a.distance - b.distance);

                displayHospitals();
                initMap();
            } catch (error) {
                console.error('Error loading hospitals:', error);
                showError();
            }
        }

        // Display hospitals list
        function displayHospitals() {
            const container = document.getElementById('hospitalsList');
            container.innerHTML = '';

            if (hospitals.length === 0) {
                container.innerHTML = `
                    <div class="bg-white rounded-2xl shadow-md p-12 text-center">
                        <div class="text-6xl mb-4">🏥</div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-2">No Hospitals Found</h3>
                        <p class="text-slate-600">No hospitals available in the system</p>
                    </div>
                `;
                return;
            }

            hospitals.forEach((hospital, index) => {
                const isNearest = index === 0;
                const distanceKm = hospital.distance.toFixed(2);
                const services = Array.isArray(hospital.services) ? hospital.services : JSON.parse(hospital.services);
                
                const card = document.createElement('div');
                card.className = `bg-white rounded-2xl shadow-md p-6 ${isNearest ? 'border-l-4 border-green-600' : ''}`;
                card.innerHTML = `
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            ${isNearest ? '<div class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-bold uppercase mb-2">🏆 Nearest Hospital</div>' : ''}
                            <h3 class="text-xl font-bold text-slate-900 mb-1">${hospital.name}</h3>
                            <p class="text-sm text-slate-500 mb-2">📍 ${hospital.address}</p>
                        </div>
                        <div class="text-right ml-4">
                            <div class="text-2xl font-bold text-blue-600">${distanceKm} km</div>
                            <div class="text-xs text-slate-500">away</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <div class="text-slate-500 text-xs mb-1">Type</div>
                            <div class="font-semibold text-slate-900 text-sm">${hospital.type}</div>
                        </div>
                        <div>
                            <div class="text-slate-500 text-xs mb-1">Area</div>
                            <div class="font-semibold text-slate-900 text-sm">${hospital.area}</div>
                        </div>
                        <div>
                            <div class="text-slate-500 text-xs mb-1">Rating</div>
                            <div class="font-semibold text-orange-600 text-sm">⭐ ${hospital.rating}</div>
                        </div>
                        <div>
                            <div class="text-slate-500 text-xs mb-1">Hours</div>
                            <div class="font-semibold text-slate-900 text-sm">${hospital.hours}</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="text-slate-500 text-xs mb-2">Services</div>
                        <div class="flex flex-wrap gap-2">
                            ${services.map(s => `<span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-semibold">${s}</span>`).join('')}
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button onclick="getDirections(${hospital.lat}, ${hospital.lng}, '${hospital.name}')" 
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold text-sm">
                            🗺️ Directions
                        </button>
                        ${hospital.doctor_available ? '<span class="px-4 py-2 bg-purple-100 text-purple-700 rounded-xl font-semibold text-sm">👨‍⚕️ Doctor Available</span>' : ''}
                    </div>
                `;
                container.appendChild(card);
            });

            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('contentState').classList.remove('hidden');
        }

        // Initialize map
        function initMap() {
            map = L.map('map').setView([userLat, userLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // User location marker
            const userIcon = L.divIcon({
                html: '<div style="background: #3B82F6; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
                className: 'custom-marker',
                iconSize: [20, 20]
            });
            
            userMarker = L.marker([userLat, userLng], { icon: userIcon })
                .addTo(map)
                .bindPopup('<b>Your Location</b>')
                .openPopup();

            // Hospital markers
            hospitals.slice(0, 10).forEach((hospital, index) => {
                const markerColor = index === 0 ? '#10B981' : '#3B82F6';
                const hospitalIcon = L.divIcon({
                    html: `<div style="background: ${markerColor}; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>`,
                    className: 'hospital-marker',
                    iconSize: [16, 16]
                });

                const distanceKm = hospital.distance.toFixed(2);
                L.marker([hospital.lat, hospital.lng], { icon: hospitalIcon })
                    .addTo(map)
                    .bindPopup(`
                        <b>${hospital.name}</b><br>
                        <span style="color: #666;">${hospital.address}</span><br>
                        <span style="color: #3B82F6; font-weight: bold;">${distanceKm} km away</span><br>
                        <span style="color: #F59E0B;">⭐ ${hospital.rating}</span>
                    `);
            });

            // Fit map to show all markers
            const bounds = [
                [userLat, userLng],
                ...hospitals.slice(0, 10).map(h => [h.lat, h.lng])
            ];
            map.fitBounds(bounds, { padding: [50, 50] });
        }

        // Get directions
        function getDirections(lat, lng, name) {
            const url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
            window.open(url, '_blank');
        }

        // Show error
        function showError() {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('errorState').classList.remove('hidden');
        }

        // Initialize on page load
        window.addEventListener('DOMContentLoaded', getLocation);
    </script>
</body>
</html>
