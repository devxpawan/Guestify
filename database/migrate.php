<?php
/**
 * Multi-Villa Migration Runner
 * Run this script to apply the multi-villa migration.
 * Usage: php database/migrate.php
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'villa_reservation';

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error() . "\n");
}

echo "Connected to database: $dbname\n\n";

$sql = file_get_contents(__DIR__ . '/migration_multi_villa.sql');
if (!$sql) {
    die("Failed to read migration file.\n");
}

// Split by semicolons for individual execution
$statements = explode(';', $sql);
$count = 0;
$errors = [];

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;

    if (mysqli_query($conn, $stmt)) {
        $count++;
        $preview = substr(preg_replace('/\s+/', ' ', $stmt), 0, 80);
        echo "[OK] $preview...\n";
    } else {
        $error = mysqli_error($conn);
        // Ignore "duplicate column" errors if migration was already partially applied
        if (strpos($error, 'Duplicate column') !== false) {
            echo "[SKIP] Column already exists: " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 80) . "\n";
            continue;
        }
        $errors[] = $error;
        echo "[ERR] " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 80) . "\n";
        echo "      Error: $error\n\n";
    }
}

echo "\n--- Migration Complete ---\n";
echo "Statements executed: $count\n";
echo "Errors: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $e) {
        echo "  - $e\n";
    }
}

mysqli_close($conn);
