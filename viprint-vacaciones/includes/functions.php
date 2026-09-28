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
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date ? $date : null;
}

function formatDate(?string $date): string
{
    if (!$date) {
        return '';
    }
    return date('d/m/Y', strtotime($date));
}

function normalizeName(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value) ?? '');
}

function completedServiceYears(string $hireDate, ?string $asOf = null): int
{
    $hire = new DateTimeImmutable($hireDate);
    $date = new DateTimeImmutable($asOf ?? date('Y-m-d'));
    if ($date < $hire) {
        return 0;
    }
    return (int)$hire->diff($date)->y;
}

function safeAnniversary(string $hireDate, int $serviceYear): DateTimeImmutable
{
    $hire = new DateTimeImmutable($hireDate);
    $year = (int)$hire->format('Y') + $serviceYear;
    $month = (int)$hire->format('m');
    $day = (int)$hire->format('d');

    if ($month === 2 && $day === 29 && !checkdate(2, 29, $year)) {
        $day = 28;
    }

    return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
}

function vacationEntitlement(int $serviceYear): int
{
    if ($serviceYear <= 0) {
        return 0;
    }
    if ($serviceYear <= 5) {
        return 10 + ($serviceYear * 2);
    }
    return 20 + (2 * (int)ceil(($serviceYear - 5) / 5));
}

function nextAnniversary(string $hireDate, int $lastProcessedYear): DateTimeImmutable
{
    return safeAnniversary($hireDate, $lastProcessedYear + 1);
}

function syncVacationAllocations(PDO $pdo, ?int $employeeId = null): void
{
    $sql = 'SELECT id, fecha_ingreso, ultimo_anio_procesado FROM empleados WHERE activo = 1';
    $params = [];
    if ($employeeId !== null) {
        $sql .= ' AND id = :id';
        $params['id'] = $employeeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll();
    $today = new DateTimeImmutable(date('Y-m-d'));

    foreach ($employees as $employee) {
        $last = (int)$employee['ultimo_anio_procesado'];
        $changed = false;

        while (true) {
            $serviceYear = $last + 1;
            $anniversary = safeAnniversary($employee['fecha_ingreso'], $serviceYear);
            if ($anniversary > $today) {
                break;
            }

            $days = vacationEntitlement($serviceYear);
            $insert = $pdo->prepare(
                'INSERT IGNORE INTO movimientos_vacaciones
                (empleado_id, tipo, fecha, dias, anio_servicio, descripcion)
                VALUES (:empleado_id, "ASIGNACION_ANUAL", :fecha, :dias, :anio_servicio, :descripcion)'
            );
            $insert->execute([
                'empleado_id' => (int)$employee['id'],
                'fecha' => $anniversary->format('Y-m-d'),
                'dias' => $days,
                'anio_servicio' => $serviceYear,
                'descripcion' => "Asignación automática por {$serviceYear} año(s) de servicio",
            ]);

            $last = $serviceYear;
            $changed = true;
        }

        if ($changed) {
            $update = $pdo->prepare('UPDATE empleados SET ultimo_anio_procesado = :last WHERE id = :id');
            $update->execute(['last' => $last, 'id' => (int)$employee['id']]);
        }
    }
}

function employeeBalance(PDO $pdo, int $employeeId): array
{
    $stmt = $pdo->prepare('SELECT saldo_inicial FROM empleados WHERE id = :id');
    $stmt->execute(['id' => $employeeId]);
    $employee = $stmt->fetch();
    if (!$employee) {
        return [
            'saldo_inicial' => 0,
            'asignaciones' => 0,
            'ajustes' => 0,
            'autorizados' => 0,
            'pendientes' => 0,
            'disponibles' => 0,
            'solicitables' => 0,
        ];
    }

    $stmt = $pdo->prepare(
        'SELECT
            COALESCE(SUM(CASE WHEN tipo = "ASIGNACION_ANUAL" THEN dias ELSE 0 END), 0) AS asignaciones,
            COALESCE(SUM(CASE WHEN tipo = "AJUSTE" THEN dias ELSE 0 END), 0) AS ajustes
         FROM movimientos_vacaciones
         WHERE empleado_id = :id'
    );
    $stmt->execute(['id' => $employeeId]);
    $moves = $stmt->fetch();

    $stmt = $pdo->prepare(
        'SELECT
            COALESCE(SUM(CASE WHEN estado = "AUTORIZADA" THEN dias_solicitados ELSE 0 END), 0) AS autorizados,
            COALESCE(SUM(CASE WHEN estado = "PENDIENTE" THEN dias_solicitados ELSE 0 END), 0) AS pendientes
         FROM solicitudes_vacaciones
         WHERE empleado_id = :id'
    );
    $stmt->execute(['id' => $employeeId]);
    $requests = $stmt->fetch();

    $initial = (int)$employee['saldo_inicial'];
    $allocations = (int)$moves['asignaciones'];
    $adjustments = (int)$moves['ajustes'];
    $authorized = (int)$requests['autorizados'];
    $pending = (int)$requests['pendientes'];
    $available = $initial + $allocations + $adjustments - $authorized;

    return [
        'saldo_inicial' => $initial,
        'asignaciones' => $allocations,
        'ajustes' => $adjustments,
        'autorizados' => $authorized,
        'pendientes' => $pending,
        'disponibles' => $available,
        'solicitables' => max(0, $available - $pending),
    ];
}

function countWorkingDaysMonSat(string $startDate, string $endDate): int
{
    $start = new DateTimeImmutable($startDate);
    $end = new DateTimeImmutable($endDate);
    if ($end < $start) {
        return 0;
    }

    $days = 0;
    for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
        if ((int)$date->format('N') !== 7) {
            $days++;
        }
    }
    return $days;
}

function nextWorkingDay(string $date): string
{
    $next = (new DateTimeImmutable($date))->modify('+1 day');
    while ((int)$next->format('N') === 7) {
        $next = $next->modify('+1 day');
    }
    return $next->format('Y-m-d');
}

function generateVacationFolio(int $id, string $date): string
{
    return 'VAC-' . date('Y', strtotime($date)) . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
}

function statusBadge(string $status): string
{
    return match ($status) {
        'AUTORIZADA' => 'success',
        'RECHAZADA' => 'danger',
        'CANCELADA' => 'secondary',
        default => 'warning text-dark',
    };
}

function statusLabel(string $status): string
{
    return match ($status) {
        'AUTORIZADA' => 'Autorizada',
        'RECHAZADA' => 'Rechazada',
        'CANCELADA' => 'Cancelada',
        default => 'Pendiente',
    };
}

function currentVacationCycle(string $hireDate): array
{
    $years = completedServiceYears($hireDate);
    if ($years === 0) {
        $start = new DateTimeImmutable($hireDate);
        $end = safeAnniversary($hireDate, 1)->modify('-1 day');
        return [
            'service_year' => 0,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'entitlement' => 0,
        ];
    }

    $start = safeAnniversary($hireDate, $years);
    $end = safeAnniversary($hireDate, $years + 1)->modify('-1 day');
    return [
        'service_year' => $years,
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d'),
        'entitlement' => vacationEntitlement($years),
    ];
}
