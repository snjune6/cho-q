<?php

declare(strict_types=1);

/**
 * 마이그레이션 실행: php sql/migrate.php
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$migrationFile = __DIR__ . '/migrations/001_users.sql';
if (!is_file($migrationFile)) {
    fwrite(STDERR, "Migration file not found.\n");
    exit(1);
}

$sql = file_get_contents($migrationFile);
if ($sql === false) {
    fwrite(STDERR, "Cannot read migration file.\n");
    exit(1);
}

$pdo = db();

try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        google_id VARCHAR(64) NOT NULL,
        email VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL DEFAULT "",
        avatar_url VARCHAR(500) NOT NULL DEFAULT "",
        role VARCHAR(16) NOT NULL DEFAULT "general",
        last_login_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_google_id (google_id),
        UNIQUE KEY uk_email (email(191))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    echo "OK: users table\n";

    $columns = $pdo->query("SHOW COLUMNS FROM cars LIKE 'user_id'")->fetchAll();
    if ($columns === []) {
        $pdo->exec('ALTER TABLE cars ADD COLUMN user_id INT UNSIGNED NULL AFTER id');
        $pdo->exec('ALTER TABLE cars ADD KEY idx_cars_user (user_id)');
        echo "OK: cars.user_id column\n";
    } else {
        echo "SKIP: cars.user_id already exists\n";
    }

    $roleColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetchAll();
    if ($roleColumn === []) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(16) NOT NULL DEFAULT 'general' AFTER avatar_url");
        $pdo->exec("UPDATE users SET role = 'general' WHERE role IS NULL OR role = ''");
        echo "OK: users.role column\n";
    } else {
        echo "SKIP: users.role already exists\n";
    }

    $pdo->exec('ALTER TABLE driver_status MODIFY custom_message TEXT NOT NULL');
    echo "OK: driver_status.custom_message as TEXT\n";

    $activeColumn = $pdo->query("SHOW COLUMNS FROM cars LIKE 'is_active'")->fetchAll();
    if ($activeColumn === []) {
        $pdo->exec('ALTER TABLE cars ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER pin_hash');
        echo "OK: cars.is_active column\n";
    } else {
        echo "SKIP: cars.is_active already exists\n";
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS status_audit_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        car_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NULL,
        status_key VARCHAR(32) NOT NULL,
        custom_message TEXT NOT NULL,
        ip_address VARCHAR(45) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_audit_car_created (car_id, created_at),
        KEY idx_audit_user (user_id),
        CONSTRAINT fk_audit_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE,
        CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    echo "OK: status_audit_log table\n";

    $guestIp = $pdo->query("SHOW COLUMNS FROM guest_messages LIKE 'ip_address'")->fetchAll();
    if ($guestIp === []) {
        $pdo->exec('ALTER TABLE guest_messages ADD COLUMN ip_address VARCHAR(45) NULL AFTER message');
        $pdo->exec('ALTER TABLE guest_messages ADD KEY idx_guest_ip_car_created (car_id, ip_address, created_at)');
        echo "OK: guest_messages.ip_address column\n";
    } else {
        echo "SKIP: guest_messages.ip_address already exists\n";
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS reports (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        car_id INT UNSIGNED NOT NULL,
        reason VARCHAR(32) NOT NULL,
        detail VARCHAR(500) NOT NULL DEFAULT "",
        reporter_ip VARCHAR(45) NULL,
        status VARCHAR(16) NOT NULL DEFAULT "pending",
        resolved_by INT UNSIGNED NULL,
        resolved_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_reports_status_created (status, created_at),
        KEY idx_reports_car (car_id),
        CONSTRAINT fk_reports_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE,
        CONSTRAINT fk_reports_resolver FOREIGN KEY (resolved_by) REFERENCES users (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    echo "OK: reports table\n";

    echo "Migration complete.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . "\n");
    exit(1);
}
