<?php

declare(strict_types=1);

/**
 * PHP 내장 서버용 라우터 (로컬 개발)
 * 실행: php -S localhost:8080 router.php
 */
require_once __DIR__ . '/includes/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rtrim($uri, '/') ?: '/';

if ($uri === '/auth/google/callback' && isset($_GET['code'])) {
    try {
        handle_google_oauth_callback((string) $_GET['code']);
        header('Location: /my');
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        $message = ($config['app']['env'] ?? '') === 'local'
            ? 'Google login error: ' . $e->getMessage()
            : 'Google login failed.';
        exit($message);
    }
}

$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
