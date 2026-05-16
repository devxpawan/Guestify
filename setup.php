<?php
$host = 'localhost';
$user = 'root';
$pass = '';

$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

$schema = file_get_contents(__DIR__ . '/config/schema.sql');
$seed = file_get_contents(__DIR__ . '/config/seed.sql');

if (mysqli_multi_query($conn, $schema)) {
    while (mysqli_next_result($conn)) {;}
    echo "Schema created successfully.\n";
}

if (mysqli_multi_query($conn, $seed)) {
    while (mysqli_next_result($conn)) {;}
    echo "Seed data inserted successfully.\n";
}

mysqli_close($conn);
echo "Setup complete! Login with admin / password\n";
?>
