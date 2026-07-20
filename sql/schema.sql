-- Cho-Q 초기 스키마 (MariaDB 10.x / MySQL 5.7+ / 카페24 호환)
-- utf8mb4 + 구형 InnoDB(767byte) 환경: email UNIQUE 는 191자 prefix 사용
-- 카페24 phpMyAdmin에서 실행

SET NAMES utf8mb4;
SET time_zone = '+09:00';

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    google_id       VARCHAR(64)  NOT NULL,
    email           VARCHAR(255) NOT NULL,
    name            VARCHAR(100) NOT NULL DEFAULT '',
    avatar_url      VARCHAR(500) NOT NULL DEFAULT '',
    role            VARCHAR(16)  NOT NULL DEFAULT 'general',
    last_login_at   DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_google_id (google_id),
    UNIQUE KEY uk_email (email(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cars (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    car_code    VARCHAR(32)  NOT NULL,
    pin_hash    VARCHAR(255) NOT NULL,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_car_code (car_code),
    KEY idx_cars_user (user_id),
    CONSTRAINT fk_cars_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS driver_status (
    car_id          INT UNSIGNED NOT NULL PRIMARY KEY,
    status_key      VARCHAR(32)  NOT NULL DEFAULT 'nervous',
    custom_message  TEXT         NOT NULL,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_status_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guest_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    car_id      INT UNSIGNED NOT NULL,
    message     VARCHAR(200) NOT NULL,
    ip_address  VARCHAR(45)  NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_car_created (car_id, created_at),
    KEY idx_guest_ip_car_created (car_id, ip_address, created_at),
    CONSTRAINT fk_message_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS status_audit_log (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    car_id          INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NULL,
    status_key      VARCHAR(32)  NOT NULL,
    custom_message  TEXT         NOT NULL,
    ip_address      VARCHAR(45)  NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_car_created (car_id, created_at),
    KEY idx_audit_user (user_id),
    CONSTRAINT fk_audit_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    car_id       INT UNSIGNED NOT NULL,
    reason       VARCHAR(32)  NOT NULL,
    detail       VARCHAR(500) NOT NULL DEFAULT '',
    reporter_ip  VARCHAR(45)  NULL,
    status       VARCHAR(16)  NOT NULL DEFAULT 'pending',
    resolved_by  INT UNSIGNED NULL,
    resolved_at  DATETIME     NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_reports_status_created (status, created_at),
    KEY idx_reports_car (car_id),
    CONSTRAINT fk_reports_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_resolver FOREIGN KEY (resolved_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 초기 데이터(데모 QR 등)는 sql/seed.sql 을 실행하세요.
