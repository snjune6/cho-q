<?php
/** @var array $car */
/** @var array $presets */
/** @var array|null $status */
$carCode = $car['car_code'];
$currentKey = $status['status_key'] ?? 'nervous';
$customMessage = ($currentKey === 'custom') ? (string) ($status['custom_message'] ?? '') : '';
?>

<section class="card console-intro">
    <h1>운전자 콘솔 🎛️</h1>
    <p>차량 <strong><?= e($carCode) ?></strong> — 지금 상태를 선택하세요</p>
</section>

<form id="consoleForm" class="console-form" data-car="<?= e($carCode) ?>">

    <div class="status-grid">
        <?php foreach ($presets as $key => $preset): ?>
            <label class="status-option">
                <input type="radio" name="status_key" value="<?= e($key) ?>"
                    <?= $key === $currentKey ? 'checked' : '' ?>>
                <span class="status-option-inner">
                    <span class="status-option-icon"><?= e($preset['icon']) ?></span>
                    <span class="status-option-label"><?= e($preset['label']) ?></span>
                </span>
            </label>
        <?php endforeach; ?>
    </div>

    <div class="field custom-field" id="customField" hidden>
        <span>직접 입력 메시지</span>
        <textarea id="customMessageEditor" name="custom_message" placeholder="예: 신호 대기 중이에요, 잠시만요!"><?= htmlspecialchars($customMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
        <p class="form-hint">굵게·기울임·목록·이미지(2MB) 사용 가능 (텍스트 200자, 스크립트 차단)</p>
    </div>

    <button type="submit" class="btn btn-primary btn-block">상태 업데이트</button>
    <p class="form-hint" id="consoleHint" role="status"></p>
</form>

<section class="card">
    <h2>내 QR 코드</h2>
    <div class="qr-preview">
        <img src="<?= e(qr_image_url($carCode)) ?>" width="200" height="200" alt="QR 코드">
        <a class="btn btn-secondary" href="<?= e(qr_image_url($carCode)) ?>" download="cho-q-<?= e($carCode) ?>.png">PNG 다운로드</a>
    </div>
    <p class="qr-url"><a href="<?= e(car_public_url($carCode)) ?>"><?= e(car_public_url($carCode)) ?></a></p>
</section>

<script>
window.ChoQPage = { mode: 'console', carCode: <?= json_encode($carCode, JSON_UNESCAPED_UNICODE) ?> };
</script>
