-- 001: Google 로그인 사용자 + 차량 소유자 연동
-- 기존 DB에 적용: php sql/migrate.php 또는 phpMyAdmin에서 실행

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    google_id       VARCHAR(64)  NOT NULL,
    email           VARCHAR(255) NOT NULL,
    name            VARCHAR(100) NOT NULL DEFAULT '',
    avatar_url      VARCHAR(500) NOT NULL DEFAULT '',
    last_login_at   DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_google_id (google_id),
    UNIQUE KEY uk_email (email(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- cars.user_id: NULL = 데모/레거시, 값 있음 = 회원 소유 차량
ALTER TABLE cars
    ADD COLUMN IF NOT EXISTS user_id INT UNSIGNED NULL AFTER id,
    ADD KEY IF NOT EXISTS idx_cars_user (user_id);

-- MariaDB 10.5+ supports IF NOT EXISTS on ADD COLUMN; older versions may need manual check.
-- FK는 기존 데이터 호환을 위해 별도 추가 (user_id가 NULL인 demo 행 유지)
-- ALTER TABLE cars ADD CONSTRAINT fk_cars_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
