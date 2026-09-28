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

function validTime(?string $time): ?string
{
    if (!$time) {
        return null;
    }

    $object = DateTime::createFromFormat('H:i', $time);
    return $object && $object->format('H:i') === $time ? $time : null;
}

function formatDate(string $date): string
{
    return date('d/m/Y', strtotime($date));
}

function formatTime(?string $time): string
{
    if (!$time) {
        return 'Pendiente de registrar';
    }

    return date('H:i', strtotime($time)) . ' h';
}

function minutesBetween(string $start, string $end): int
{
    $startTime = DateTime::createFromFormat('H:i:s', strlen($start) === 5 ? $start . ':00' : $start);
    $endTime = DateTime::createFromFormat('H:i:s', strlen($end) === 5 ? $end . ':00' : $end);

    if (!$startTime || !$endTime) {
        return 0;
    }

    $minutes = (int)(($endTime->getTimestamp() - $startTime->getTimestamp()) / 60);
    return max(0, $minutes);
}

function formatDuration(int $minutes): string
{
    $hours = intdiv($minutes, 60);
    $rest = $minutes % 60;

    if ($hours > 0 && $rest > 0) {
        return $hours . ' h ' . $rest . ' min';
    }
    if ($hours > 0) {
        return $hours . ' h';
    }
    return $rest . ' min';
}

function reasonLabel(string $value): string
{
    return match ($value) {
        'medico' => 'Consulta o asunto médico',
        'familiar' => 'Asunto familiar',
        'tramite' => 'Trámite personal',
        'laboral' => 'Actividad o diligencia de trabajo',
        'emergencia' => 'Emergencia',
        default => 'Otro asunto personal',
    };
}

function timeTreatmentLabel(string $value): string
{
    return match ($value) {
        'con_goce' => 'Permiso con goce de sueldo',
        'sin_goce' => 'Permiso sin goce de sueldo',
        'reposicion' => 'Tiempo sujeto a reposición',
        'salida_laboral' => 'Salida por actividad de trabajo',
        default => 'Por definir por el patrón',
    };
}

function statusLabel(string $value): string
{
    return match ($value) {
        'autorizado' => 'Autorizado',
        'cancelado' => 'Cancelado',
        default => 'Pendiente de firma',
    };
}

function statusBadge(string $value): string
{
    return match ($value) {
        'autorizado' => 'success',
        'cancelado' => 'secondary',
        default => 'warning',
    };
}

function generateFolio(int $id, string $date): string
{
    return 'PS-' . date('Y', strtotime($date)) . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
}

function getEmployee(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM empleados WHERE id = ?');
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    return $employee ?: null;
}

function getPermit(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT p.*, e.nombre AS empleado_nombre
         FROM permisos_salida p
         INNER JOIN empleados e ON e.id = p.empleado_id
         WHERE p.id = ?'
    );
    $stmt->execute([$id]);
    $permit = $stmt->fetch();
    return $permit ?: null;
}
