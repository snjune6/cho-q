<?php

declare(strict_types=1);

/**
 * 설정 템플릿 — 이 파일을 config.php 로 복사한 뒤 값을 채우세요.
 * config.php 는 .gitignore 대상입니다.
 */
return [
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'dbname'  => 'cho_q',
        'user'    => 'your_db_user',
        'pass'    => 'your_db_password',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'env'       => 'local',           // local | production
        'base_url'  => 'http://localhost:8080',
        'timezone'  => 'Asia/Seoul',
        'poll_interval_ms' => 3000,
    ],
];
