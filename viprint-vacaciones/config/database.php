<?php
declare(strict_types=1);

$host = 'localhost';
$db   = 'viprintc_vacaciones';
$user = 'viprintc_vacaciones';
$pass = 'cWBD5byfX4dHA9y8y2AP';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('No fue posible conectar con la base de datos. Revisa config/database.php e importa database/schema.sql.');
}
