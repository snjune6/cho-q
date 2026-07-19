<!DOCTYPE html>
<html lang="ko" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="초보 운전자의 속사정을 QR로 공유하는 모바일 웹 서비스, 초큐">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="<?= e($pageClass ?? '') ?>">
<header class="site-header">
    <a href="/" class="logo">🚗 초큐</a>
    <button type="button" class="theme-toggle" id="themeToggle" aria-label="다크모드 전환">🌙</button>
</header>

<main class="site-main">
    <?php require $viewPath; ?>
</main>

<footer class="site-footer">
    <p>초보의 속사정을 큐알(QR)로</p>
</footer>

<script src="/assets/js/app.js" defer></script>
</body>
</html>
