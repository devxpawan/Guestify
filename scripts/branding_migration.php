<?php
require_once __DIR__ . '/../config/database.php';

$query = "
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    company_email VARCHAR(255) NOT NULL,
    currency_symbol VARCHAR(10) NOT NULL,
    logo_path VARCHAR(255) DEFAULT NULL,
    favicon_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
";

if (mysqli_query($conn, $query)) {
    echo "Settings table created successfully.\n";
} else {
    die("Error creating table: " . mysqli_error($conn) . "\n");
}

// Check if default row exists
$check = mysqli_query($conn, "SELECT COUNT(*) FROM settings");
$count = mysqli_fetch_row($check)[0];

if ($count == 0) {
    $insert = "INSERT INTO settings (company_name, company_email, currency_symbol) VALUES ('VillaRS', 'info@villars.com', '$')";
    if (mysqli_query($conn, $insert)) {
        echo "Default settings inserted successfully.\n";
    } else {
        die("Error inserting default settings: " . mysqli_error($conn) . "\n");
    }
} else {
    echo "Default settings already exist.\n";
}
?>
