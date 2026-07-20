<?php

declare(strict_types=1);

function blocked_words(): array
{
    static $words = null;
    if ($words !== null) {
        return $words;
    }

    $path = dirname(__DIR__) . '/config/moderation_words.php';
    if (is_file($path)) {
        $loaded = require $path;
        $words = is_array($loaded) ? array_values(array_filter($loaded, 'is_string')) : [];
    } else {
        $words = [];
    }

    return $words;
}

function normalize_for_moderation(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/[\s\*\_\-\.]+/u', '', $text);

    return $text;
}

function text_contains_blocked_word(string $text): bool
{
    $normalized = normalize_for_moderation($text);
    if ($normalized === '') {
        return false;
    }

    foreach (blocked_words() as $word) {
        $needle = normalize_for_moderation($word);
        if ($needle !== '' && mb_strpos($normalized, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function assert_no_blocked_content(string $text): void
{
    if ($text === '') {
        return;
    }

    $plain = strpos($text, '<') !== false ? custom_message_plain_text($text) : trim($text);
    if ($plain === '') {
        return;
    }

    if (text_contains_blocked_word($plain)) {
        throw new InvalidArgumentException('부적절한 표현이 포함되어 있어 저장할 수 없습니다.');
    }
}

function assert_guest_message_allowed(string $message): void
{
    assert_no_blocked_content($message);
}

function guest_message_rate_limit(): int
{
    return max(1, (int) (app_config()['guest_message_rate_limit'] ?? 5));
}

function guest_message_rate_window_sec(): int
{
    return max(60, (int) (app_config()['guest_message_rate_window_sec'] ?? 600));
}

function assert_guest_message_rate_limit(int $carId): void
{
    $ip = request_client_ip();
    if ($ip === null) {
        return;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM guest_messages
         WHERE car_id = ? AND ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)'
    );
    $stmt->execute([$carId, $ip, guest_message_rate_window_sec()]);
    $count = (int) $stmt->fetchColumn();

    if ($count >= guest_message_rate_limit()) {
        throw new InvalidArgumentException(
            '잠시 후 다시 시도해 주세요. (같은 QR에는 짧은 시간에 ' . guest_message_rate_limit() . '번까지만 보낼 수 있어요)'
        );
    }
}

function insert_guest_message(int $carId, string $message): int
{
    $ip = request_client_ip();
    $stmt = db()->prepare('INSERT INTO guest_messages (car_id, message, ip_address) VALUES (?, ?, ?)');
    $stmt->execute([$carId, $message, $ip]);

    return (int) db()->lastInsertId();
}
