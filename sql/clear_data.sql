-- Cho-Q 전체 데이터 삭제 (테이블 구조 유지)
-- phpMyAdmin 또는 mysql CLI에서 실행
-- ⚠️ 모든 회원·QR·방명록·신고·감사 로그가 삭제됩니다.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE reports;
TRUNCATE TABLE status_audit_log;
TRUNCATE TABLE guest_messages;
TRUNCATE TABLE driver_status;
TRUNCATE TABLE cars;
TRUNCATE TABLE users;

SET FOREIGN_KEY_CHECKS = 1;
