<?php
/** @var array $car */
/** @var array $presets */
/** @var array|null $status */
$carCode = $car['car_code'];
$currentKey = $status['status_key'] ?? 'nervous';
?>

<section class="card console-intro">
    <h1>운전자 콘솔 🎛️</h1>
    <p>차량 <strong><?= e($carCode) ?></strong> — 지금 상태를 선택하세요</p>
</section>

<form id="consoleForm" class="console-form" data-car="<?= e($carCode) ?>">
    <label class="field">
        <span>PIN 번호</span>
        <input type="password" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="8" placeholder="4자리 PIN" required>
    </label>

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

    <label class="field custom-field" id="customField" hidden>
        <span>직접 입력 메시지</span>
        <textarea name="custom_message" rows="2" maxlength="200" placeholder="예: 신호 대기 중이에요, 잠시만요!"></textarea>
    </label>

    <button type="submit" class="btn btn-primary btn-block">상태 업데이트</button>
    <p class="form-hint" id="consoleHint" role="status"></p>
</form>

<section class="card">
    <h2>QR 페이지</h2>
    <p class="qr-url"><a href="<?= e(car_public_url($carCode)) ?>"><?= e(car_public_url($carCode)) ?></a></p>
</section>

<script>
window.ChoQPage = { mode: 'console', carCode: <?= json_encode($carCode, JSON_UNESCAPED_UNICODE) ?> };
</script>
