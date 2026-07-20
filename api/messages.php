<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $carCode = trim((string) ($_GET['car'] ?? ''));
        $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));

        if ($carCode === '') {
            json_response(['error' => 'car 파라미터가 필요합니다.'], 400);
        }

        $car = find_car_by_code($carCode);
        if (!$car) {
            json_response(['error' => '차량을 찾을 수 없습니다.'], 404);
        }

        if (!car_is_active($car)) {
            json_response(['error' => '이 QR은 이용이 중지되었습니다.'], 403);
        }

        $stmt = db()->prepare(
            'SELECT id, message, created_at FROM guest_messages WHERE car_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, (int) $car['id'], PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $messages = array_map(static function (array $row): array {
            return [
                'id'         => (int) $row['id'],
                'message'    => $row['message'],
                'created_at' => iso8601($row['created_at']),
            ];
        }, $rows);

        json_response(['messages' => $messages]);
    }

    if ($method === 'POST') {
        $body = read_json_body();
        $carCode = trim((string) ($body['car'] ?? ''));
        $message = trim((string) ($body['message'] ?? ''));

        if ($carCode === '' || $message === '') {
            json_response(['error' => 'car, message 가 필요합니다.'], 400);
        }

        if (mb_strlen($message) > 200) {
            json_response(['error' => '메시지는 200자 이하여야 합니다.'], 400);
        }

        $car = find_car_by_code($carCode);
        if (!$car) {
            json_response(['error' => '차량을 찾을 수 없습니다.'], 404);
        }

        if (!car_is_active($car)) {
            json_response(['error' => '이 QR은 이용이 중지되었습니다.'], 403);
        }

        try {
            assert_guest_message_allowed($message);
            assert_guest_message_rate_limit((int) $car['id']);
        } catch (InvalidArgumentException $e) {
            json_response(['error' => $e->getMessage()], 400);
        }

        $messageId = insert_guest_message((int) $car['id'], $message);

        json_response([
            'ok' => true,
            'message' => [
                'id'         => $messageId,
                'message'    => $message,
                'created_at' => (new DateTimeImmutable())->format(DateTime::ATOM),
            ],
        ], 201);
    }

    json_response(['error' => '허용되지 않은 메서드입니다.'], 405);
} catch (Throwable $e) {
    $message = app_config()['env'] === 'local' ? $e->getMessage() : '서버 오류가 발생했습니다.';
    json_response(['error' => $message], 500);
}
