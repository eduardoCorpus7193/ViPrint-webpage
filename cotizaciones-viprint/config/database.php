<?php
// Configuración inicial para XAMPP. En hosting cambia estos datos.
$host = '127.0.0.1';
$db   = 'cotizaciones_viprint';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
$options = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
);

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo '<h1>Error de conexión a la base de datos</h1>';
    echo '<p>Revisa config/database.php, el nombre de la base, usuario, contraseña y permisos.</p>';
    echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    exit;
}
