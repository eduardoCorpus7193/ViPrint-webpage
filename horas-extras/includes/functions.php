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
    if (!$time || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
        return null;
    }

    return $time . ':00';
}

function timeShort(?string $time): string
{
    return $time ? substr($time, 0, 5) : '';
}

function hoursBetween(string $start, string $end): float
{
    [$startHour, $startMinute] = array_map('intval', explode(':', substr($start, 0, 5)));
    [$endHour, $endMinute] = array_map('intval', explode(':', substr($end, 0, 5)));

    $startMinutes = ($startHour * 60) + $startMinute;
    $endMinutes = ($endHour * 60) + $endMinute;

    if ($endMinutes < $startMinutes) {
        $endMinutes += 24 * 60;
    }

    return round(($endMinutes - $startMinutes) / 60, 2);
}

function formatHours(float $hours): string
{
    $totalMinutes = (int)round($hours * 60);
    $wholeHours = intdiv($totalMinutes, 60);
    $minutes = $totalMinutes % 60;

    return $minutes === 0
        ? "{$wholeHours} h"
        : "{$wholeHours} h {$minutes} min";
}

function formatDate(string $date): string
{
    return date('d/m/Y', strtotime($date));
}

function generateFolio(int $id, string $date): string
{
    return 'HE-' . date('Y', strtotime($date)) . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
}
