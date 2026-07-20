-- 004: 운전자 상태 변경 audit log
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
