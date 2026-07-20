<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['error' => 'POST만 허용됩니다.'], 405);
}

try {
    $body = read_json_body();
    $carCode = trim((string) ($body['car'] ?? ''));
    $reason = trim((string) ($body['reason'] ?? ''));
    $detail = trim((string) ($body['detail'] ?? ''));

    if ($carCode === '' || $reason === '') {
        json_response(['error' => 'car, reason 이 필요합니다.'], 400);
    }

    $car = find_car_by_code($carCode);
    if (!$car) {
        json_response(['error' => '차량을 찾을 수 없습니다.'], 404);
    }

    if (!car_is_active($car)) {
        json_response(['error' => '이 QR은 이용이 중지되었습니다.'], 403);
    }

    try {
        $reportId = create_report((int) $car['id'], $reason, $detail);
    } catch (InvalidArgumentException $e) {
        json_response(['error' => $e->getMessage()], 400);
    }

    json_response([
        'ok'        => true,
        'report_id' => $reportId,
        'message'   => '신고가 접수되었습니다. 검토 후 조치하겠습니다.',
    ], 201);
} catch (Throwable $e) {
    $message = app_config()['env'] === 'local' ? $e->getMessage() : '서버 오류가 발생했습니다.';
    json_response(['error' => $message], 500);
}
