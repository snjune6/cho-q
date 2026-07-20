<?php
// views/car.php
require_once __DIR__ . '/../config/config.local.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

if (!isset($_SESSION['user_id'])) {
    die("로그인이 필요합니다.");
}

$userId = $_SESSION['user_id'];

// QR 코드에 담을 개인화된 데이터 (예: 프로필 페이지 URL)
$qrData = "http://localhost/profile?id=" . $userId;

$qrCode = QrCode::create($qrData);
$writer = new PngWriter();
$result = $writer->write($qrCode);

// Base64 형태로 변환하여 HTML 이미지 태그에 바로 출력
$qrImageBase64 = base64_encode($result->getString());

/** @var array $car */
/** @var array $status */
/** @var array $display */
/** @var int $poll_interval */
$carCode = $car['car_code'];
$updatedAt = iso8601($status['updated_at']);
?>

<h2>환영합니다! 회원님만의 고유 QR 코드입니다.</h2>
<img src="data:image/png;base64,<?= $qrImageBase64 ?>" alt="Personalized QR Code">

<section class="status-card" id="statusCard"
         data-car="<?= e($carCode) ?>"
         data-since="<?= e($updatedAt) ?>"
         data-poll-interval="<?= (int) $poll_interval ?>">
    <div class="status-icon" id="statusIcon"><?= e($display['icon']) ?></div>
    <h1 class="status-label" id="statusLabel"><?= e($display['label']) ?></h1>
    <p class="status-message" id="statusMessage"><?= e($display['message']) ?></p>
    <p class="status-meta">실시간 업데이트 중…</p>
</section>

<section class="card guestbook" id="guestbook">
    <h2>따뜻한 한마디 💬</h2>
    <p class="guestbook-desc">익명으로 응원 메시지를 남겨 주세요</p>

    <form id="messageForm" class="message-form">
        <textarea name="message" rows="2" maxlength="200" placeholder="천천히 가셔도 돼요! 화이팅!" required></textarea>
        <button type="submit" class="btn btn-primary">보내기</button>
    </form>

    <ul class="message-list" id="messageList"></ul>
</section>

<script>
window.ChoQPage = {
    mode: 'car',
    carCode: <?= json_encode($carCode, JSON_UNESCAPED_UNICODE) ?>,
    since: <?= json_encode($updatedAt, JSON_UNESCAPED_UNICODE) ?>,
    pollInterval: <?= (int) $poll_interval ?>
};
</script>
