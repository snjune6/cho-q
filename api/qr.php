<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/qr.php';

try {
    $carCode = trim((string) ($_GET['car'] ?? ''));
    if ($carCode === '') {
        http_response_code(400);
        exit('car 파라미터가 필요합니다.');
    }

    $car = find_car_by_code($carCode);
    if (!$car) {
        http_response_code(404);
        exit('차량을 찾을 수 없습니다.');
    }

    $url = car_public_url($carCode);
    $png = qr_png_for_url($url);

    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    header('Content-Length: ' . strlen($png));
    echo $png;
} catch (Throwable $e) {
    http_response_code(500);
    if (app_config()['env'] === 'local') {
        exit('QR 생성 오류: ' . $e->getMessage());
    }
    exit('QR 생성에 실패했습니다.');
}
