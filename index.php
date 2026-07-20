<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ((app_config()['env'] ?? 'local') === 'local') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rtrim($uri, '/') ?: '/';

$pageTitle = '초큐 Cho-Q';
$pageClass = '';
$view = 'home';
$viewData = [];

if ($uri === '/') {
    $view = 'home';
    $pageTitle = '초큐 — 초보의 속사정을 큐알로';
} elseif ($uri === '/my') {
    $user = require_login();
    $flashMessage = null;
    $flashError = null;
    $newCarInfo = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register_car') {
        try {
            $preferredCode = trim((string) ($_POST['car_code'] ?? ''));
            $result = register_car_for_user((int) $user['id'], $preferredCode !== '' ? $preferredCode : null);
            $newCarInfo = [
                'car_code' => $result['car']['car_code'],
            ];
            $flashMessage = '내 QR이 등록됐어요!';
        } catch (Throwable $e) {
            $flashError = $e->getMessage();
        }
    }

    $view = 'my';
    $pageTitle = '내 초큐';
    $pageClass = 'page-my';
    $viewData = [
        'user'         => $user,
        'cars'         => find_cars_by_user_id((int) $user['id']),
        'flashMessage' => $flashMessage,
        'flashError'   => $flashError,
        'newCarInfo'   => $newCarInfo,
    ];
} elseif ($uri === '/auth/logout') {
    logout_user();
    header('Location: /');
    exit;
} elseif ($uri === '/auth/google/callback') {
    if (!isset($_GET['code'])) {
        header('Location: /');
        exit;
    }
    try {
        handle_google_oauth_callback((string) $_GET['code']);
        header('Location: /my');
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        $view = 'error';
        $pageTitle = 'Google 로그인 실패';
        // 배포 직에 원인 확인이 필요해 실제 메시지를 노출합니다.
        // (redirect_uri_mismatch, invalid_client 등)
        $message = 'Google 로그인 실패: ' . $e->getMessage();
        $viewData = ['message' => $message];
    }
} elseif ($uri === '/admin') {
    $adminUser = require_admin();
    $flashMessage = null;
    $flashError = null;
    $searchCode = trim((string) ($_GET['car'] ?? ''));
    $carDetail = null;
    $auditLogs = [];
    $pendingReports = find_pending_reports(30);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $targetCode = trim((string) ($_POST['car_code'] ?? ''));
        if ($targetCode !== '') {
            $searchCode = $targetCode;
        }

        if ($action === 'set_active' && $targetCode !== '') {
            $targetCar = find_car_by_code($targetCode);
            if (!$targetCar) {
                $flashError = '차량을 찾을 수 없습니다.';
            } else {
                $makeActive = (string) ($_POST['is_active'] ?? '') === '1';
                set_car_active((int) $targetCar['id'], $makeActive);
                $flashMessage = $makeActive ? 'QR 이용을 다시 허용했습니다.' : 'QR 이용을 중지했습니다.';
            }
        } elseif ($action === 'resolve_report') {
            $reportId = (int) ($_POST['report_id'] ?? 0);
            try {
                resolve_report($reportId, (int) $adminUser['id'], false);
                $flashMessage = '신고를 처리 완료했습니다.';
            } catch (InvalidArgumentException $e) {
                $flashError = $e->getMessage();
            }
        } elseif ($action === 'resolve_report_suspend') {
            $reportId = (int) ($_POST['report_id'] ?? 0);
            try {
                resolve_report($reportId, (int) $adminUser['id'], true);
                $flashMessage = '신고 처리 및 QR 이용 중지를 완료했습니다.';
            } catch (InvalidArgumentException $e) {
                $flashError = $e->getMessage();
            }
        }

        $pendingReports = find_pending_reports(30);
    }

    if ($searchCode !== '') {
        $carDetail = find_car_admin_detail($searchCode);
        if (!$carDetail) {
            $flashError = $flashError ?? '해당 코드의 QR을 찾을 수 없습니다.';
        } else {
            $auditLogs = find_status_audit_logs((int) $carDetail['id'], 30);
        }
    }

    $view = 'admin';
    $pageTitle = '관리자 — QR 조회';
    $pageClass = 'page-admin';
    $viewData = [
        'adminUser'    => $adminUser,
        'searchCode'   => $searchCode,
        'carDetail'      => $carDetail,
        'auditLogs'      => $auditLogs,
        'pendingReports' => $pendingReports,
        'flashMessage'   => $flashMessage,
        'flashError'   => $flashError,
    ];
} elseif (preg_match('#^/c/([a-zA-Z0-9_-]+)$#', $uri, $m)) {
    $carCode = $m[1];
    $car = find_car_by_code($carCode);
    if (!$car) {
        http_response_code(404);
        $view = 'error';
        $pageTitle = '차량을 찾을 수 없어요';
        $viewData = ['message' => 'QR 코드가 올바른지 확인해 주세요.'];
    } elseif (!car_is_active($car)) {
        http_response_code(403);
        $view = 'error';
        $pageTitle = '이용이 중지된 QR';
        $viewData = ['message' => '이 QR은 운영 정책에 따라 현재 이용이 중지되었습니다.'];
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
        $consoleUser = current_user();
        if (!$consoleUser) {
            http_response_code(403);
            $view = 'error';
            $pageTitle = '로그인이 필요해요';
            $viewData = ['message' => '운전자 콘솔은 Google 로그인 후, 본인이 등록한 QR만 사용할 수 있어요.'];
        } elseif (!user_can_manage_car($consoleUser, $car)) {
            http_response_code(403);
            $view = 'error';
            $pageTitle = '접근 권한이 없어요';
            $viewData = ['message' => '이 QR의 소유자만 콘솔을 사용할 수 있어요.'];
        } elseif (!car_is_active($car) && !is_admin()) {
            http_response_code(403);
            $view = 'error';
            $pageTitle = '이용이 중지된 QR';
            $viewData = ['message' => '이 QR은 이용이 중지되어 상태를 변경할 수 없습니다.'];
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
