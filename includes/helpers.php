<?php

declare(strict_types=1);

function app_config(): array
{
    global $config;
    return $config['app'] ?? [];
}

function base_url(): string
{
    return rtrim((string) (app_config()['base_url'] ?? ''), '/');
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function status_presets(): array
{
    return [
        'parking'    => ['label' => '주차 연습 중…', 'icon' => '🅿️', 'desc' => '땀 뻘뻘, 천천히 기다려 주세요'],
        'hill_start' => ['label' => '언덕길 출발 대기', 'icon' => '⛰️', 'desc' => '출발 신호 기다리는 중이에요'],
        'sorry'      => ['label' => '죄송해요', 'icon' => '🙏', 'desc' => '천천히 갈게요, 양해 부탁드려요'],
        'thanks'     => ['label' => '감사합니다', 'icon' => '💛', 'desc' => '양보해 주셔서 고마워요'],
        'nervous'    => ['label' => '긴장 중', 'icon' => '😰', 'desc' => '초보 운전자예요, 응원 부탁해요'],
        'custom'     => ['label' => '직접 입력', 'icon' => '✏️', 'desc' => '나만의 메시지'],
    ];
}

function resolve_status_display(string $statusKey, string $customMessage): array
{
    $presets = status_presets();
    if ($statusKey === 'custom') {
        $message = trim($customMessage) !== '' ? $customMessage : '상태 메시지를 입력해 주세요';
        return ['label' => '나의 한마디', 'icon' => '✏️', 'message' => $message];
    }

    $preset = $presets[$statusKey] ?? $presets['nervous'];
    return [
        'label'   => $preset['label'],
        'icon'    => $preset['icon'],
        'message' => $preset['desc'],
    ];
}

function find_car_by_code(string $carCode): ?array
{
    $stmt = db()->prepare('SELECT id, car_code, pin_hash, created_at FROM cars WHERE car_code = ? LIMIT 1');
    $stmt->execute([$carCode]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_driver_status(int $carId): ?array
{
    $stmt = db()->prepare(
        'SELECT car_id, status_key, custom_message, updated_at FROM driver_status WHERE car_id = ? LIMIT 1'
    );
    $stmt->execute([$carId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function verify_car_pin(array $car, string $pin): bool
{
    return password_verify($pin, $car['pin_hash']);
}

function iso8601($date) {
    return date('c', strtotime($date));
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function car_public_url(string $carCode): string
{
    return base_url() . '/c/' . rawurlencode($carCode);
}

function car_console_url(string $carCode): string
{
    return base_url() . '/console/' . rawurlencode($carCode);
}
