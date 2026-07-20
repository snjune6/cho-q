<?php

declare(strict_types=1);

/** @return array<string, string> key => 한글 라벨 */
function user_roles(): array
{
    return [
        'general' => '일반',
        'user'    => '사용자',
        'admin'   => '관리자',
    ];
}

function normalize_user_role(string $role): string
{
    $roles = user_roles();
    return array_key_exists($role, $roles) ? $role : 'general';
}

function user_role_label(string $role): string
{
    $roles = user_roles();
    $role = normalize_user_role($role);
    return $roles[$role];
}

function is_valid_user_role(string $role): bool
{
    return array_key_exists($role, user_roles());
}

function find_user_by_google_id(string $googleId): ?array
{
    $stmt = db()->prepare(
        'SELECT id, google_id, email, name, avatar_url, role, last_login_at, created_at
         FROM users WHERE google_id = ? LIMIT 1'
    );
    $stmt->execute([$googleId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_user_by_id(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT id, google_id, email, name, avatar_url, role, last_login_at, created_at
         FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare(
        'SELECT id, google_id, email, name, avatar_url, role, last_login_at, created_at
         FROM users WHERE email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Google 프로필로 회원가입 또는 로그인 (upsert).
 * 최초 가입 시 role = general (일반).
 */
function login_or_register_google_user(array $profile): array
{
    $googleId = (string) ($profile['google_id'] ?? '');
    $email = (string) ($profile['email'] ?? '');
    $name = (string) ($profile['name'] ?? '');
    $avatarUrl = (string) ($profile['avatar_url'] ?? '');

    if ($googleId === '' || $email === '') {
        throw new InvalidArgumentException('Google 프로필 정보가 부족합니다.');
    }

    $existing = find_user_by_google_id($googleId);
    if (!$existing) {
        $existing = find_user_by_email($email);
    }

    if ($existing) {
        $stmt = db()->prepare(
            'UPDATE users SET google_id = ?, email = ?, name = ?, avatar_url = ?, last_login_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$googleId, $email, $name, $avatarUrl, (int) $existing['id']]);
        $userId = (int) $existing['id'];
        $isNew = false;
    } else {
        $stmt = db()->prepare(
            'INSERT INTO users (google_id, email, name, avatar_url, role, last_login_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$googleId, $email, $name, $avatarUrl, 'general']);
        $userId = (int) db()->lastInsertId();
        $isNew = true;
    }

    $user = find_user_by_id($userId);
    if (!$user) {
        throw new RuntimeException('사용자 저장 후 조회에 실패했습니다.');
    }

    return [
        'id'          => (int) $user['id'],
        'google_id'   => (string) $user['google_id'],
        'email'       => (string) $user['email'],
        'name'        => (string) $user['name'],
        'avatar_url'  => (string) $user['avatar_url'],
        'role'        => normalize_user_role((string) $user['role']),
        'is_new'      => $isNew,
    ];
}

function find_cars_by_user_id(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT id, car_code, user_id, is_active, created_at FROM cars WHERE user_id = ? ORDER BY created_at ASC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll() ?: [];
}

function generate_car_code(): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    do {
        $code = 'u';
        for ($i = 0; $i < 7; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $exists = find_car_by_code($code);
    } while ($exists !== null);

    return $code;
}

function unused_pin_hash(): string
{
    static $hash = null;
    if ($hash === null) {
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    }
    return $hash;
}

function register_car_for_user(int $userId, ?string $preferredCode = null): array
{
    $carCode = $preferredCode !== null && $preferredCode !== ''
        ? strtolower(trim($preferredCode))
        : generate_car_code();

    if (!preg_match('/^[a-z0-9_-]{3,32}$/', $carCode)) {
        throw new InvalidArgumentException('차량 코드는 3~32자 영문·숫자·_- 만 사용할 수 있습니다.');
    }

    if (find_car_by_code($carCode) !== null) {
        throw new InvalidArgumentException('이미 사용 중인 차량 코드입니다.');
    }

    $pinHash = unused_pin_hash();

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO cars (user_id, car_code, pin_hash) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $carCode, $pinHash]);
        $carId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO driver_status (car_id, status_key, custom_message) VALUES (?, ?, ?)'
        );
        $stmt->execute([$carId, 'nervous', '초보 운전자예요, 응원 부탁해요 😊']);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    $car = find_car_by_code($carCode);
    if (!$car) {
        throw new RuntimeException('차량 등록 후 조회에 실패했습니다.');
    }

    return ['car' => $car];
}

function user_session_payload(array $user): array
{
    $role = normalize_user_role((string) ($user['role'] ?? 'general'));

    return [
        'id'         => (int) $user['id'],
        'google_id'  => (string) $user['google_id'],
        'email'      => (string) $user['email'],
        'name'       => (string) $user['name'],
        'avatar_url' => (string) ($user['avatar_url'] ?? ''),
        'role'       => $role,
        'role_label' => user_role_label($role),
    ];
}

function current_user_role(): string
{
    $user = current_user();
    return $user ? normalize_user_role((string) ($user['role'] ?? 'general')) : 'general';
}

function is_admin(): bool
{
    return current_user_role() === 'admin';
}

function is_member_user(): bool
{
    return current_user_role() === 'user';
}

function require_admin(): array
{
    $user = require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('관리자만 접근할 수 있습니다.');
    }
    return $user;
}

function update_user_role(int $userId, string $role): void
{
    if (!is_valid_user_role($role)) {
        throw new InvalidArgumentException('유효하지 않은 등급입니다.');
    }

    $stmt = db()->prepare('UPDATE users SET role = ? WHERE id = ?');
    $stmt->execute([$role, $userId]);
}
