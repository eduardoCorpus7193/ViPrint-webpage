<?php
declare(strict_types=1);

$host = 'localhost';
$db   = 'viprintc_permisos_salida';
$user = 'viprintc_permisos_salida';
$pass = 'Qamp3pJ8kpEn2E4PQUdC';
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
    exit('No fue posible conectar con la base de datos. Revisa config/database.php e importa database/schema.sql.');
}
