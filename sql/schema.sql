-- Cho-Q 초기 스키마 (MariaDB 10.x / MySQL 8 호환)
-- 카페24 phpMyAdmin에서 실행

SET NAMES utf8mb4;
SET time_zone = '+09:00';

CREATE TABLE IF NOT EXISTS cars (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    car_code    VARCHAR(32)  NOT NULL,
    pin_hash    VARCHAR(255) NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_car_code (car_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS driver_status (
    car_id          INT UNSIGNED NOT NULL PRIMARY KEY,
    status_key      VARCHAR(32)  NOT NULL DEFAULT 'nervous',
    custom_message  VARCHAR(200) NOT NULL DEFAULT '',
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_status_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guest_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    car_id      INT UNSIGNED NOT NULL,
    message     VARCHAR(200) NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_car_created (car_id, created_at),
    CONSTRAINT fk_message_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 데모 차량 (PIN: 1234)
INSERT INTO cars (car_code, pin_hash) VALUES (
    'demo',
    '$2y$10$wf8oDqalNXtfbOZ8wqMmDu.9swdcBbNWt/Vwe1ImtDWKLNjyq2mOu'
);
INSERT INTO driver_status (car_id, status_key, custom_message)
SELECT id, 'parking', '주차 연습 중이에요. 천천히 기다려 주세요 🙏'
FROM cars WHERE car_code = 'demo';
