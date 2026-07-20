<?php

declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('config/config.php 가 없습니다. config.example.php 를 복사해 설정하세요.');
}

$config = require $configPath;

$localConfigPath = dirname(__DIR__) . '/config/config.local.php';
if (is_file($localConfigPath)) {
    $local = require $localConfigPath;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Seoul');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/sanitize.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/cars.php';
require_once __DIR__ . '/moderation.php';
require_once __DIR__ . '/reports.php';
require_once __DIR__ . '/qr.php';
require_once __DIR__ . '/google_auth.php';

ensure_session_started();
