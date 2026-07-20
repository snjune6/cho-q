-- Cho-Q 초기 데이터 (배포용)
-- 실행 순서: 1) sql/schema.sql  2) 이 파일
-- 카페24 phpMyAdmin · MariaDB 10.x / MySQL 5.7+
--
-- ※ Google 로그인 1회 후 관리자 지정:
--    UPDATE users SET role = 'admin' WHERE email = '운영자@gmail.com';
--
-- ※ 데모 QR (/c/demo)
--    - user_id NULL = 공용 체험용
--    - 콘솔(/console/demo)은 관리자만 접근 가능

SET NAMES utf8mb4;
SET time_zone = '+09:00';

-- ---------------------------------------------------------------------------
-- 데모 QR
-- pin_hash: 레거시 컬럼(미사용), NOT NULL 만족용 더미 해시
-- ---------------------------------------------------------------------------
INSERT INTO cars (user_id, car_code, pin_hash, is_active)
SELECT NULL, 'demo', '$2y$10$wf8oDqalNXtfbOZ8wqMmDu.9swdcBbNWt/Vwe1ImtDWKLNjyq2mOu', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM cars WHERE car_code = 'demo');

INSERT INTO driver_status (car_id, status_key, custom_message)
SELECT c.id, 'parking', '주차 연습 중이에요. 천천히 기다려 주세요 🙏'
FROM cars c
WHERE c.car_code = 'demo'
  AND NOT EXISTS (SELECT 1 FROM driver_status ds WHERE ds.car_id = c.id);

-- 데모 방명록 샘플 (해당 QR에 메시지가 없을 때만)
INSERT INTO guest_messages (car_id, message, ip_address)
SELECT c.id, '천천히 가셔도 돼요! 화이팅!', NULL
FROM cars c
WHERE c.car_code = 'demo'
  AND NOT EXISTS (SELECT 1 FROM guest_messages gm WHERE gm.car_id = c.id);

INSERT INTO guest_messages (car_id, message, ip_address)
SELECT c.id, '👍 응원합니다!', NULL
FROM cars c
WHERE c.car_code = 'demo'
  AND (SELECT COUNT(*) FROM guest_messages gm WHERE gm.car_id = c.id) = 1;
