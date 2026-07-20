-- 006: QR 신고
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
