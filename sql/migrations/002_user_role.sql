-- 002: 사용자 등급 (general=일반, user=사용자, admin=관리자)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role VARCHAR(16) NOT NULL DEFAULT 'general' AFTER avatar_url;

UPDATE users SET role = 'general' WHERE role IS NULL OR role = '';
