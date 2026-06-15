<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(419);
        exit('La sesión del formulario expiró. Regresa e inténtalo nuevamente.');
    }
}

function validDate(?string $date): ?string
{
    if (!$date) {
        return null;
    }

    $object = DateTime::createFromFormat('Y-m-d', $date);
    return $object && $object->format('Y-m-d') === $date ? $date : null;
}

function formatDate(string $date): string
{
    return date('d/m/Y', strtotime($date));
}

function formatMoney(float $amount): string
{
    return '$' . number_format($amount, 2, '.', ',') . ' MXN';
}

function documentTypeLabel(string $type): string
{
    return $type === 'bono_efectivo'
        ? 'Bono semanal en efectivo'
        : 'Pago normal por transferencia';
}

function documentTypeShort(string $type): string
{
    return $type === 'bono_efectivo' ? 'Bono en efectivo' : 'Transferencia';
}

function generateFolio(int $id, string $date, string $type): string
{
    $prefix = $type === 'bono_efectivo' ? 'BE' : 'PT';
    return $prefix . '-' . date('Y', strtotime($date)) . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
}

function getEmployee(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM empleados WHERE id = ?');
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    return $employee ?: null;
}

function getDocument(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT d.*, e.nombre AS empleado_nombre
         FROM documentos_nomina d
         INNER JOIN empleados e ON e.id = d.empleado_id
         WHERE d.id = ?'
    );
    $stmt->execute([$id]);
    $document = $stmt->fetch();
    return $document ?: null;
}
