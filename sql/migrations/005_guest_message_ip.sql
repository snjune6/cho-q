-- 005: 방명록 IP (rate limit·추적용)
ALTER TABLE guest_messages
    ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) NULL AFTER message;

ALTER TABLE guest_messages
    ADD KEY IF NOT EXISTS idx_guest_ip_car_created (car_id, ip_address, created_at);
