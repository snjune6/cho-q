<?php
declare(strict_types=1);
// router.php
require_once __DIR__ . '/config/config.local.php';

if (isset($_GET['code'])) {
    // 코드를 통해 액세스 토큰 획득
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);

    // 구글 프로필 정보 가져오기
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();

    $email =  $google_account_info->email;
    $google_id = $google_account_info->id;

    // TODO: 여기서 DB에 사용자가 있는지 확인하고($google_id 기반),
    // 없으면 신규 가입 처리, 있으면 세션에 로그인 정보를 저장합니다.
    $_SESSION['user_id'] = $google_id;

    // QR 코드를 보여줄 페이지로 이동
    header('Location: /views/car.php');
    exit();
}



/**
 * PHP 내장 서버용 라우터 (로컬 개발)
 * 실행: php -S localhost:8080 router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
