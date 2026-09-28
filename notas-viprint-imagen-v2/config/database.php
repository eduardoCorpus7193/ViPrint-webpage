<?php
// Configura estos datos según tu hosting.
$host = 'localhost';
$db   = 'viprintc_v2';
$user = 'viprintc_v2';
$pass = '2uGxvxUhAa3jgvCbzYgP';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('No fue posible conectar con la base de datos. Revisa config/database.php. Detalle: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
