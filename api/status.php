<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $carCode = trim((string) ($_GET['car'] ?? ''));
        if ($carCode === '') {
            json_response(['error' => 'car 파라미터가 필요합니다.'], 400);
        }

        $car = find_car_by_code($carCode);
        if (!$car) {
            json_response(['error' => '차량을 찾을 수 없습니다.'], 404);
        }

        $status = get_driver_status((int) $car['id']);
        if (!$status) {
            json_response(['error' => '상태 정보가 없습니다.'], 404);
        }

        json_response([
            'status_key'     => $status['status_key'],
            'custom_message' => $status['custom_message'],
            'updated_at'     => iso8601($status['updated_at']),
            'display'        => resolve_status_display($status['status_key'], $status['custom_message']),
        ]);
    }

    if ($method === 'POST') {
        $body = read_json_body();
        $carCode = trim((string) ($body['car'] ?? ''));
        $pin = (string) ($body['pin'] ?? '');
        $statusKey = trim((string) ($body['status_key'] ?? ''));
        $customMessage = trim((string) ($body['custom_message'] ?? ''));

        if ($carCode === '' || $pin === '' || $statusKey === '') {
            json_response(['error' => 'car, pin, status_key 가 필요합니다.'], 400);
        }

        if (!array_key_exists($statusKey, status_presets())) {
            json_response(['error' => '유효하지 않은 status_key 입니다.'], 400);
        }

        if ($statusKey === 'custom' && $customMessage === '') {
            json_response(['error' => 'custom 상태는 custom_message 가 필요합니다.'], 400);
        }

        if (mb_strlen($customMessage) > 200) {
            json_response(['error' => '메시지는 200자 이하여야 합니다.'], 400);
        }

        $car = find_car_by_code($carCode);
        if (!$car || !verify_car_pin($car, $pin)) {
            json_response(['error' => '인증에 실패했습니다.'], 403);
        }

        $stmt = db()->prepare(
            'UPDATE driver_status SET status_key = ?, custom_message = ?, updated_at = NOW() WHERE car_id = ?'
        );
        $stmt->execute([$statusKey, $customMessage, (int) $car['id']]);

        $status = get_driver_status((int) $car['id']);
        json_response([
            'ok'             => true,
            'status_key'     => $status['status_key'],
            'custom_message' => $status['custom_message'],
            'updated_at'     => iso8601($status['updated_at']),
            'display'        => resolve_status_display($status['status_key'], $status['custom_message']),
        ]);
    }

    json_response(['error' => '허용되지 않은 메서드입니다.'], 405);
} catch (Throwable $e) {
    $message = app_config()['env'] === 'local' ? $e->getMessage() : '서버 오류가 발생했습니다.';
    json_response(['error' => $message], 500);
}
