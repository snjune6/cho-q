<?php

declare(strict_types=1);

function google_config(): array
{
    global $config;
    return $config['google'] ?? [];
}

function google_client(): ?Google_Client
{
    $google = google_config();
    if (($google['client_id'] ?? '') === '' || ($google['client_secret'] ?? '') === '') {
        return null;
    }

    static $client = null;
    if ($client instanceof Google_Client) {
        return $client;
    }

    require_once dirname(__DIR__) . '/vendor/autoload.php';

    $client = new Google_Client();
    $client->setClientId($google['client_id']);
    $client->setClientSecret($google['client_secret']);
    $client->setRedirectUri($google['redirect_uri']);
    $client->addScope('email');
    $client->addScope('profile');

    return $client;
}

function google_login_url(): ?string
{
    $client = google_client();
    return $client ? $client->createAuthUrl() : null;
}

function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function handle_google_oauth_callback(string $code): array
{
    ensure_session_started();

    $client = google_client();
    if (!$client) {
        throw new RuntimeException('Google OAuth 설정이 없습니다. config.php 의 google 항목을 확인하세요.');
    }

    $google = google_config();
    $redirectUri = (string) ($google['redirect_uri'] ?? '');
    if ($redirectUri === '') {
        throw new RuntimeException('google.redirect_uri 가 비어 있습니다.');
    }

    // 콜백마다 동일 URI로 토큰 교환 (설정·Console 값 불일치 방지)
    $client->setRedirectUri($redirectUri);

    $token = $client->authenticate($code);
    if (is_array($token) && isset($token['error'])) {
        $desc = (string) ($token['error_description'] ?? $token['error']);
        throw new RuntimeException($desc . ' (redirect_uri=' . $redirectUri . ')');
    }

    if ($client->getAccessToken() === null) {
        throw new RuntimeException(
            '액세스 토큰을 받지 못했습니다. Google Console 리디렉션 URI와 config.php 값이 같은지 확인하세요: '
            . $redirectUri
        );
    }

    $oauth2 = new Google_Service_Oauth2($client);
    $profile = $oauth2->userinfo->get();

    if (empty($profile->id) || empty($profile->email)) {
        throw new RuntimeException('Google 프로필(이메일)을 가져오지 못했습니다. OAuth 동의 화면 범위를 확인하세요.');
    }

    $user = login_or_register_google_user([
        'google_id'   => (string) $profile->id,
        'email'       => (string) $profile->email,
        'name'        => (string) ($profile->name ?? ''),
        'avatar_url'  => (string) ($profile->picture ?? ''),
    ]);

    $_SESSION['user'] = user_session_payload($user);

    return $user;
}

function current_user(): ?array
{
    ensure_session_started();
    if (!isset($_SESSION['user'])) {
        return null;
    }

    $sessionUser = $_SESSION['user'];
    if (!isset($sessionUser['role'], $sessionUser['role_label']) && isset($sessionUser['id'])) {
        $dbUser = find_user_by_id((int) $sessionUser['id']);
        if ($dbUser) {
            $_SESSION['user'] = user_session_payload($dbUser);
        }
    }

    return $_SESSION['user'] ?? null;
}

function current_user_id(): ?int
{
    $user = current_user();
    return $user ? (int) $user['id'] : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: /');
        exit;
    }
    return $user;
}

function logout_user(): void
{
    ensure_session_started();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
