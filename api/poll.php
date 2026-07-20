<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

try {
    $carCode = trim((string) ($_GET['car'] ?? ''));
    $since = trim((string) ($_GET['since'] ?? ''));

    if ($carCode === '') {
        json_response(['error' => 'car 파라미터가 필요합니다.'], 400);
    }

    $car = find_car_by_code($carCode);
    if (!$car) {
        json_response(['error' => '차량을 찾을 수 없습니다.'], 404);
    }

    $carId = (int) $car['id'];
    $status = get_driver_status($carId);
    if (!$status) {
        json_response(['error' => '상태 정보가 없습니다.'], 404);
    }

    $statusUpdatedAt = iso8601($status['updated_at']);
    $statusChanged = $since === '' || $since < $statusUpdatedAt;

    $newMessages = [];
    if ($since !== '') {
        $sinceDb = (new DateTimeImmutable($since))->format('Y-m-d H:i:s');
        $stmt = db()->prepare(
            'SELECT id, message, created_at FROM guest_messages
             WHERE car_id = ? AND created_at > ? ORDER BY created_at ASC'
        );
        $stmt->execute([$carId, $sinceDb]);
        foreach ($stmt->fetchAll() as $row) {
            $newMessages[] = [
                'id'         => (int) $row['id'],
                'message'    => $row['message'],
                'created_at' => iso8601($row['created_at']),
            ];
        }
    }

    $changed = $statusChanged || count($newMessages) > 0;

    if (!$changed) {
        json_response(['changed' => false]);
    }

    json_response([
        'changed'    => true,
        'status'     => [
            'status_key'     => $status['status_key'],
            'custom_message' => $status['custom_message'],
            'updated_at'     => $statusUpdatedAt,
            'display'        => resolve_status_display($status['status_key'], $status['custom_message']),
        ],
        'messages'   => $newMessages,
        'polled_at'  => (new DateTimeImmutable())->format(DateTime::ATOM),
    ]);
} catch (Throwable $e) {
    $message = app_config()['env'] === 'local' ? $e->getMessage() : '서버 오류가 발생했습니다.';
    json_response(['error' => $message], 500);
}
