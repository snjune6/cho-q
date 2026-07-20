-- 카페24 등 767byte 인덱스 제한 환경: users.email UNIQUE prefix 수정
-- 이미 schema.sql 로 users 테이블 생성에 실패했다면, users 가 없을 때 schema.sql 을 다시 실행하세요.
-- users 가 이미 있다면 아래만 실행:

-- ALTER TABLE users DROP INDEX uk_email;
-- ALTER TABLE users ADD UNIQUE KEY uk_email (email(191));
