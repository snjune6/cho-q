<?php

declare(strict_types=1);

function car_is_active(array $car): bool
{
    return (int) ($car['is_active'] ?? 1) === 1;
}

function user_owns_car(array $user, array $car): bool
{
    $ownerId = $car['user_id'] ?? null;
    if ($ownerId === null || $ownerId === '') {
        return false;
    }

    return (int) $ownerId === (int) $user['id'];
}

function user_can_manage_car(array $user, array $car): bool
{
    if (normalize_user_role((string) ($user['role'] ?? 'general')) === 'admin') {
        return true;
    }

    return user_owns_car($user, $car);
}

function require_login_json(): array
{
    $user = current_user();
    if (!$user) {
        json_response(['error' => '로그인이 필요합니다.'], 401);
    }

    return $user;
}

/**
 * @return array{user: array, car: array}
 */
function require_car_manage_json(string $carCode): array
{
    $user = require_login_json();

    $car = find_car_by_code($carCode);
    if (!$car) {
        json_response(['error' => '차량을 찾을 수 없습니다.'], 404);
    }

    if (!user_can_manage_car($user, $car)) {
        json_response(['error' => '이 QR을 수정할 권한이 없습니다.'], 403);
    }

    if (!car_is_active($car) && !is_admin()) {
        json_response(['error' => '이 QR은 이용이 중지되었습니다.'], 403);
    }

    return ['user' => $user, 'car' => $car];
}

function set_car_active(int $carId, bool $active): void
{
    $stmt = db()->prepare('UPDATE cars SET is_active = ? WHERE id = ?');
    $stmt->execute([$active ? 1 : 0, $carId]);
}

function find_car_admin_detail(string $carCode): ?array
{
    $stmt = db()->prepare(
        'SELECT c.id, c.user_id, c.car_code, c.is_active, c.created_at,
                u.email AS owner_email, u.name AS owner_name, u.role AS owner_role,
                ds.status_key, ds.custom_message, ds.updated_at AS status_updated_at
         FROM cars c
         LEFT JOIN users u ON u.id = c.user_id
         LEFT JOIN driver_status ds ON ds.car_id = c.id
         WHERE c.car_code = ?
         LIMIT 1'
    );
    $stmt->execute([$carCode]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function custom_message_preview(string $html, int $maxLen = 120): string
{
    $plain = custom_message_plain_text(safe_custom_message_html($html));
    if ($plain === '') {
        if (count_message_images($html) > 0) {
            return '(이미지 포함)';
        }
        return '';
    }

    if (mb_strlen($plain) <= $maxLen) {
        return $plain;
    }

    return mb_substr($plain, 0, $maxLen) . '…';
}

function request_client_ip(): ?string
{
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($ip === '') {
        return null;
    }

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
}

function log_status_change(int $carId, ?int $userId, string $statusKey, string $customMessage): void
{
    $stmt = db()->prepare(
        'INSERT INTO status_audit_log (car_id, user_id, status_key, custom_message, ip_address)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $carId,
        $userId,
        $statusKey,
        $customMessage,
        request_client_ip(),
    ]);
}

function find_status_audit_logs(int $carId, int $limit = 30): array
{
    $limit = min(100, max(1, $limit));
    $stmt = db()->prepare(
        'SELECT l.id, l.status_key, l.custom_message, l.ip_address, l.created_at,
                u.email AS actor_email, u.name AS actor_name
         FROM status_audit_log l
         LEFT JOIN users u ON u.id = l.user_id
         WHERE l.car_id = ?
         ORDER BY l.created_at DESC
         LIMIT ' . $limit
    );
    $stmt->execute([$carId]);

    return $stmt->fetchAll() ?: [];
}
