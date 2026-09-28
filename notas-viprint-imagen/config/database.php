<?php
// Configuración de conexión a MySQL.
// En hosting normalmente debes cambiar estos datos por los que te da el panel.
$DB_HOST = 'localhost';
$DB_NAME = 'viprintc_notas_viprint_imagen';
$DB_USER = 'viprintc_notas_viprint_imagen';
$DB_PASS = 'qVcd39EPr73hr4uM7YuG';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
$options = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
);

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo '<h1>Error de conexión a la base de datos</h1>';
    echo '<p>Revisa el archivo <strong>config/database.php</strong>.</p>';
    echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    exit;
}
