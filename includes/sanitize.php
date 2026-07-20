<?php

declare(strict_types=1);

/** 허용 HTML 태그 (콘솔 Jodit와 동일) */
function custom_message_allowed_tags(): string
{
    return '<p><br><b><strong><i><em><u><ul><ol><li><img>';
}

function sanitize_custom_message(string $html): string
{
    $html = trim(str_replace("\0", '', $html));
    if ($html === '') {
        return '';
    }

    $html = preg_replace(
        '/<(script|style|iframe|object|embed|form|input|button|link|meta|base|svg|math)[^>]*>.*?<\\/\\1>/is',
        '',
        $html
    );
    $html = preg_replace(
        '/<(script|style|iframe|object|embed|form|input|button|link|meta|base|svg|math)[^>]*\\/?>/i',
        '',
        $html
    );

    $html = strip_tags($html, custom_message_allowed_tags());
    $html = preg_replace('/javascript\\s*:/i', '', $html);
    $html = preg_replace('/data\\s*:/i', '', $html);
    $html = preg_replace('/on\\w+\\s*=/i', '', $html);

    // img src 는 속성 제거 전에 처리해야 함
    $html = sanitize_img_tags($html);
    $html = preg_replace('/<(?!img\\b)(\\w+)(\\s[^>]*)?>/i', '<$1>', $html);
    $html = preg_replace('/<img\\s*\\/?>/i', '', $html);

    return trim($html);
}

function custom_message_plain_text(string $html): string
{
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\\s+/u', ' ', $text));
}

function custom_message_plain_length(string $html): int
{
    return mb_strlen(custom_message_plain_text($html));
}

function validate_and_sanitize_custom_message(string $html): string
{
    $sanitized = sanitize_custom_message($html);

    if ($sanitized === '') {
        return '';
    }

    if (count_message_images($sanitized) > 3) {
        throw new InvalidArgumentException('이미지는 최대 3장까지 넣을 수 있습니다.');
    }

    $plainLen = custom_message_plain_length($sanitized);
    $hasImages = count_message_images($sanitized) > 0;

    if ($plainLen > 200) {
        throw new InvalidArgumentException('메시지는 200자(텍스트 기준) 이하여야 합니다.');
    }

    if ($plainLen === 0 && !$hasImages) {
        throw new InvalidArgumentException('메시지 또는 이미지를 입력해 주세요.');
    }

    if (mb_strlen($sanitized) > 8000) {
        throw new InvalidArgumentException('메시지 HTML이 너무 깁니다.');
    }

    if ($plainLen > 0) {
        assert_no_blocked_content(custom_message_plain_text($sanitized));
    }

    return $sanitized;
}

function safe_custom_message_html(string $html): string
{
    return sanitize_custom_message($html);
}

function custom_message_has_rich_html(string $html): bool
{
    if (count_message_images($html) > 0) {
        return true;
    }

    return (bool) preg_match('/<(p|b|strong|i|em|u|ul|ol|li|br)\\b/i', $html);
}
