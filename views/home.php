<?php
/** @var string|null $loginUrl */
$loginUrl = google_login_url();
$user = current_user();
?>

<?php if ($loginUrl): ?>
<section class="card demo-links">
    <?php if ($user): ?>
        <p>안녕하세요, <strong><?= e($user['name'] !== '' ? $user['name'] : $user['email']) ?></strong> 님</p>
        <a class="btn btn-primary" href="/my">내 초큐 보기</a>
        <a class="btn btn-secondary" href="/auth/logout">로그아웃</a>
    <?php else: ?>
        <a class="btn btn-primary" href="<?= e($loginUrl) ?>">구글 계정으로 시작하기</a>
        <p class="form-hint">로그인하면 나만의 QR과 운전자 콘솔을 만들 수 있어요.</p>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="hero">
    <div class="hero-badge">Cho-Q</div>
    <h1>초보의 속사정을<br><span class="accent">큐알(QR)</span>로!</h1>
    <p class="hero-desc">
        뒷유리 QR을 스캔하면 지금 차 안 상황을 알 수 있어요.<br>
        따뜻한 한마디도 남길 수 있답니다.
    </p>
</section>

<section class="card steps">
    <h2>이렇게 써요</h2>
    <ol>
        <li><strong>QR 부착</strong> — 뒷유리에 초큐 QR 스티커를 붙여요</li>
        <li><strong>상태 변경</strong> — 운전자 콘솔에서 버튼 한 번으로 상태를 바꿔요</li>
        <li><strong>스캔 &amp; 응원</strong> — 뒤차가 스캔해 상황을 보고 응원 메시지를 남겨요</li>
    </ol>
</section>

<section class="card demo-links">
    <h2>체험해 보기</h2>
    <a class="btn btn-primary" href="/c/demo">데모 차량 보기</a>
    <a class="btn btn-secondary" href="/console/demo">운전자 콘솔</a>
</section>
