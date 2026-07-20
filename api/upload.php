<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/upload.php';

header('Content-Type: application/json; charset=utf-8');

function upload_json_error(string $message, int $status = 400): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'  => false,
        'messages' => [$message],
        'error'    => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        upload_json_error('POST만 허용됩니다.', 405);
    }

    $carCode = trim((string) ($_GET['car'] ?? $_POST['car'] ?? ''));
    if ($carCode === '') {
        upload_json_error('car 파라미터가 필요합니다.', 400);
    }

    $user = current_user();
    if (!$user) {
        upload_json_error('로그인이 필요합니다.', 401);
    }

    $car = find_car_by_code($carCode);
    if (!$car) {
        upload_json_error('차량을 찾을 수 없습니다.', 404);
    }

    if (!user_can_manage_car($user, $car)) {
        upload_json_error('이 QR을 수정할 권한이 없습니다.', 403);
    }

    if (!car_is_active($car) && !is_admin()) {
        upload_json_error('이 QR은 이용이 중지되었습니다.', 403);
    }

    $file = null;
    foreach (['files', 'file', 'image'] as $key) {
        if (isset($_FILES[$key])) {
            $file = is_array($_FILES[$key]['name'] ?? null)
                ? [
                    'name'     => $_FILES[$key]['name'][0] ?? '',
                    'type'     => $_FILES[$key]['type'][0] ?? '',
                    'tmp_name' => $_FILES[$key]['tmp_name'][0] ?? '',
                    'error'    => $_FILES[$key]['error'][0] ?? UPLOAD_ERR_NO_FILE,
                    'size'     => $_FILES[$key]['size'][0] ?? 0,
                ]
                : $_FILES[$key];
            break;
        }
    }

    if ($file === null) {
        upload_json_error('업로드 파일이 없습니다.', 400);
    }

    $saved = save_custom_message_image($carCode, $file);
    $url = $saved['url'];

    echo json_encode([
        'success' => true,
        'time'    => date('Y-m-d H:i:s'),
        'data'    => [
            'files'    => [$url],
            'path'     => '',
            'baseurl'  => '',
            'isImages' => [true],
            'messages' => [],
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (InvalidArgumentException $e) {
    upload_json_error($e->getMessage(), 400);
} catch (Throwable $e) {
    $message = app_config()['env'] === 'local' ? $e->getMessage() : '업로드에 실패했습니다.';
    upload_json_error($message, 500);
}
