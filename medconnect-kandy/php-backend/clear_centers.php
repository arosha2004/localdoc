<?php
require_once __DIR__ . '/config/database.php';
$db = getDBConnection();
$db->exec('DELETE FROM medical_centers');
echo "✅ Cleared all medical centers\n";
