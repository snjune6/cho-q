<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');


require_once __DIR__ . '/includes/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rtrim($uri, '/') ?: '/';

$pageTitle = '초큐 Cho-Q';
$pageClass = '';
$view = 'home';
$viewData = [];

if ($uri === '/') {
    $view = 'home';
    $pageTitle = '초큐 — 초보의 속사정을 큐알로';
} elseif (preg_match('#^/c/([a-zA-Z0-9_-]+)$#', $uri, $m)) {
    $carCode = $m[1];
    $car = find_car_by_code($carCode);
    if (!$car) {
        http_response_code(404);
        $view = 'error';
        $pageTitle = '차량을 찾을 수 없어요';
        $viewData = ['message' => 'QR 코드가 올바른지 확인해 주세요.'];
    } else {
        $status = get_driver_status((int) $car['id']);
        $display = resolve_status_display($status['status_key'] ?? 'nervous', $status['custom_message'] ?? '');
        $view = 'car';
        $pageTitle = '초큐 — ' . $display['label'];
        $pageClass = 'page-car';
        $viewData = [
            'car'           => $car,
            'status'        => $status,
            'display'       => $display,
            'presets'       => status_presets(),
            'poll_interval' => (int) (app_config()['poll_interval_ms'] ?? 3000),
        ];
    }
} elseif (preg_match('#^/console/([a-zA-Z0-9_-]+)$#', $uri, $m)) {
    $carCode = $m[1];
    $car = find_car_by_code($carCode);
    if (!$car) {
        http_response_code(404);
        $view = 'error';
        $pageTitle = '차량을 찾을 수 없어요';
        $viewData = ['message' => '콘솔 URL을 확인해 주세요.'];
    } else {
        $view = 'console';
        $pageTitle = '운전자 콘솔 — 초큐';
        $pageClass = 'page-console';
        $viewData = [
            'car'     => $car,
            'presets' => status_presets(),
            'status'  => get_driver_status((int) $car['id']),
        ];
    }
} else {
    http_response_code(404);
    $view = 'error';
    $pageTitle = '페이지를 찾을 수 없어요';
    $viewData = ['message' => '요청하신 페이지가 없습니다.'];
}

$viewPath = __DIR__ . '/views/' . $view . '.php';
if (!is_file($viewPath)) {
    http_response_code(500);
    exit('View not found');
}

extract($viewData, EXTR_SKIP);
require __DIR__ . '/views/layout.php';
