<?php

declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('config/config.php 가 없습니다. config.example.php 를 복사해 설정하세요.');
}

$config = require $configPath;
date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Seoul');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
