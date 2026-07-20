<?php

declare(strict_types=1);

/** @var array $user */
/** @var array $cars */
/** @var string|null $flashMessage */
/** @var string|null $flashError */
/** @var array|null $newCarInfo */

$displayName = $user['name'] !== '' ? $user['name'] : $user['email'];
?>

<section class="card my-intro">
    <h1>내 초큐 🚗</h1>
    <p>안녕하세요, <strong><?= e($displayName) ?></strong> 님</p>
    <p class="my-email"><?= e($user['email']) ?></p>
    <p class="my-role">등급: <span class="role-badge role-<?= e($user['role']) ?>"><?= e($user['role_label']) ?></span></p>
</section>

<?php if ($flashMessage): ?>
    <section class="card flash ok"><?= e($flashMessage) ?></section>
<?php endif; ?>

<?php if ($flashError): ?>
    <section class="card flash err"><?= e($flashError) ?></section>
<?php endif; ?>

<?php if ($newCarInfo): ?>
    <section class="card new-car-info">
        <h2>QR 등록 완료 🎉</h2>
        <p>아래 정보를 저장해 두세요.</p>
        <dl class="info-list">
            <dt>차량 코드</dt>
            <dd><code><?= e($newCarInfo['car_code']) ?></code></dd>
            <dt>QR 코드</dt>
            <dd class="qr-preview">
                <img src="<?= e(qr_image_url($newCarInfo['car_code'])) ?>" width="200" height="200" alt="QR 코드">
                <a class="btn btn-secondary" href="<?= e(qr_image_url($newCarInfo['car_code'])) ?>" download="cho-q-<?= e($newCarInfo['car_code']) ?>.png">PNG 다운로드</a>
            </dd>
            <dt>QR 페이지</dt>
            <dd><a href="<?= e(car_public_url($newCarInfo['car_code'])) ?>"><?= e(car_public_url($newCarInfo['car_code'])) ?></a></dd>
        </dl>
        <a class="btn btn-primary" href="<?= e(car_console_url($newCarInfo['car_code'])) ?>">운전자 콘솔 열기</a>
    </section>
<?php endif; ?>

<section class="card">
    <h2>내 차량</h2>
    <?php if ($cars === []): ?>
        <p class="empty-hint">아직 등록된 QR이 없어요. 아래에서 내 차량을 만들어 보세요.</p>
    <?php else: ?>
        <ul class="my-car-list">
            <?php foreach ($cars as $car): ?>
                <li>
                    <div class="my-car-item">
                        <img class="qr-thumb" src="<?= e(qr_image_url($car['car_code'])) ?>" width="120" height="120" alt="QR <?= e($car['car_code']) ?>">
                        <div>
                            <strong><?= e($car['car_code']) ?></strong>
                            <?php if ((int) ($car['is_active'] ?? 1) !== 1): ?>
                                <span class="status-badge status-suspended">중지됨</span>
                            <?php endif; ?>
                            <div class="my-car-links">
                                <a href="<?= e(car_public_url($car['car_code'])) ?>">QR 페이지</a>
                                <a href="<?= e(car_console_url($car['car_code'])) ?>">콘솔</a>
                                <a href="<?= e(qr_image_url($car['car_code'])) ?>" download="cho-q-<?= e($car['car_code']) ?>.png">다운로드</a>
                            </div>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="card">
    <h2>내 QR 만들기</h2>
    <p class="form-desc">차량 코드를 비우면 자동으로 생성돼요.</p>
    <form method="post" action="/my" class="register-car-form">
        <input type="hidden" name="action" value="register_car">
        <label class="field">
            <span>차량 코드 (선택)</span>
            <input type="text" name="car_code" maxlength="32" pattern="[a-zA-Z0-9_-]+" placeholder="예: mybusan01">
        </label>
        <button type="submit" class="btn btn-primary btn-block">QR 등록하기</button>
    </form>
</section>

<section class="card">
    <a class="btn btn-secondary btn-block" href="/auth/logout">로그아웃</a>
</section>
