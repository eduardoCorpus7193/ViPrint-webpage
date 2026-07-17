<?php
require_once __DIR__ . '/includes/bootstrap.php';
validate_csrf();
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nombre = trim(isset($_POST['nombre']) ? $_POST['nombre'] : '');
if ($nombre === '') {
    flash_set('danger', 'El nombre de la promoción es obligatorio.');
    redirect_to('/promociones.php' . ($id ? '?edit=' . $id : ''));
}
$descripcion = trim(isset($_POST['descripcion']) ? $_POST['descripcion'] : '');
$precio = max(0, (float)(isset($_POST['precio']) ? $_POST['precio'] : 0));
$activo = isset($_POST['activo']) ? 1 : 0;
if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE promociones SET nombre=?, descripcion=?, precio=?, activo=? WHERE id=?");
    $stmt->execute(array($nombre, $descripcion, $precio, $activo, $id));
    flash_set('success', 'Promoción actualizada.');
} else {
    $stmt = $pdo->prepare("INSERT INTO promociones (nombre, descripcion, precio, activo) VALUES (?,?,?,?)");
    $stmt->execute(array($nombre, $descripcion, $precio, $activo));
    flash_set('success', 'Promoción registrada.');
}
redirect_to('/promociones.php');
