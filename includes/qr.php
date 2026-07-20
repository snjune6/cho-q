<?php

declare(strict_types=1);

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

function qr_png_for_url(string $url, int $size = 280): string
{
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    $result = Builder::create()
        ->writer(new PngWriter())
        ->data($url)
        ->size($size)
        ->margin(12)
        ->build();

    return $result->getString();
}

function qr_data_uri_for_car(string $carCode): string
{
    $url = car_public_url($carCode);
    $png = qr_png_for_url($url);
    return 'data:image/png;base64,' . base64_encode($png);
}

function qr_image_url(string $carCode): string
{
    return '/api/qr.php?car=' . rawurlencode($carCode);
}
