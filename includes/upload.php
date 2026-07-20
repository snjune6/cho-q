<?php

declare(strict_types=1);

function uploads_base_dir(): string
{
    return dirname(__DIR__) . '/uploads/custom';
}

function uploads_public_prefix(): string
{
    return '/uploads/custom';
}

function ensure_car_upload_dir(string $carCode): string
{
    $dir = uploads_base_dir() . '/' . $carCode;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('업로드 디렉토리를 만들 수 없습니다.');
    }
    return $dir;
}

function allowed_image_mimes(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
}

function detect_upload_image_mime(string $tmpPath, string $originalName = ''): string
{
    if ($tmpPath === '' || !is_file($tmpPath)) {
        throw new InvalidArgumentException('업로드 파일을 확인할 수 없습니다.');
    }

    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);
        if (is_string($mime) && $mime !== '') {
            return $mime;
        }
    }

    if (function_exists('mime_content_type')) {
        $mime = mime_content_type($tmpPath);
        if (is_string($mime) && $mime !== '') {
            return $mime;
        }
    }

    if (function_exists('getimagesize')) {
        $info = @getimagesize($tmpPath);
        if (is_array($info) && !empty($info['mime'])) {
            return (string) $info['mime'];
        }
    }

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $extMap = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
    ];
    if (isset($extMap[$ext])) {
        return $extMap[$ext];
    }

    throw new InvalidArgumentException('jpg, png, webp, gif 만 업로드할 수 있습니다.');
}

/**
 * @return array{url:string, path:string}
 */
function save_custom_message_image(string $carCode, array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('이미지 업로드에 실패했습니다.');
    }

    $maxBytes = 2 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new InvalidArgumentException('이미지는 2MB 이하여야 합니다.');
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    $mime = detect_upload_image_mime($tmpPath, (string) ($file['name'] ?? ''));
    $allowed = allowed_image_mimes();

    if (!isset($allowed[$mime])) {
        throw new InvalidArgumentException('jpg, png, webp, gif 만 업로드할 수 있습니다.');
    }

    $ext = $allowed[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dir = ensure_car_upload_dir($carCode);
    $dest = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('이미지 저장에 실패했습니다.');
    }

    resize_uploaded_image($dest, $mime, 960);

    $url = uploads_public_prefix() . '/' . rawurlencode($carCode) . '/' . rawurlencode($filename);

    return ['url' => $url, 'path' => $dest];
}

function resize_uploaded_image(string $path, string $mime, int $maxWidth): void
{
    if (!function_exists('imagecreatetruecolor')) {
        return;
    }

    $source = false;
    switch ($mime) {
        case 'image/jpeg':
            $source = @imagecreatefromjpeg($path);
            break;
        case 'image/png':
            $source = @imagecreatefrompng($path);
            break;
        case 'image/webp':
            $source = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
            break;
        case 'image/gif':
            $source = @imagecreatefromgif($path);
            break;
    }

    if ($source === false) {
        return;
    }

    $width = imagesx($source);
    $height = imagesy($source);
    if ($width <= 0 || $height <= 0) {
        imagedestroy($source);
        return;
    }
    if ($width <= $maxWidth) {
        imagedestroy($source);
        return;
    }

    $newWidth = $maxWidth;
    $newHeight = (int) round($height * ($newWidth / $width));
    $dest = imagecreatetruecolor($newWidth, $newHeight);

    if (in_array($mime, ['image/png', 'image/webp', 'image/gif'], true)) {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
    }

    imagecopyresampled($dest, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($dest, $path, 85);
            break;
        case 'image/png':
            imagepng($dest, $path, 6);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                imagewebp($dest, $path, 85);
            }
            break;
        case 'image/gif':
            imagegif($dest, $path);
            break;
    }

    imagedestroy($source);
    imagedestroy($dest);
}

function normalize_upload_image_src(string $src): string
{
    $src = trim(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($src === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $src)) {
        $path = parse_url($src, PHP_URL_PATH);
        return is_string($path) ? $path : '';
    }

    return $src;
}

function is_allowed_upload_image_src(string $src): bool
{
    $src = normalize_upload_image_src($src);
    if ($src === '') {
        return false;
    }

    return (bool) preg_match(
        '#^/uploads/custom/[a-zA-Z0-9_-]+/[a-zA-Z0-9._-]+\\.(jpg|jpeg|png|gif|webp)$#i',
        $src
    );
}

function sanitize_img_tags(string $html): string
{
    return (string) preg_replace_callback(
        '/<img\\b[^>]*?\\ssrc=(["\']?)([^"\'\\s>]+)\\1[^>]*\\/?>/i',
        static function (array $matches): string {
            $src = normalize_upload_image_src($matches[2]);
            if (!is_allowed_upload_image_src($src)) {
                return '';
            }
            $safeSrc = htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '<img src="' . $safeSrc . '" alt="" loading="lazy">';
        },
        $html
    );
}

function count_message_images(string $html): int
{
    preg_match_all('/<img\\b[^>]*\\ssrc=/i', $html, $matches);
    return count($matches[0]);
}
