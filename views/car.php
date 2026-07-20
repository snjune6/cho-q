<?php
/** @var array $car */
/** @var array $status */
/** @var array $display */
/** @var int $poll_interval */
$carCode = $car['car_code'];
$updatedAt = iso8601($status['updated_at']);
?>

<section class="status-card" id="statusCard"
         data-car="<?= e($carCode) ?>"
         data-since="<?= e($updatedAt) ?>"
         data-poll-interval="<?= (int) $poll_interval ?>">
    <div class="status-icon" id="statusIcon"><?= e($display['icon']) ?></div>
    <h1 class="status-label" id="statusLabel"><?= e($display['label']) ?></h1>
    <div class="status-message" id="statusMessage"><?php if (!empty($display['is_html']) && !empty($display['message_html'])): ?><?= safe_custom_message_html($display['message_html']) ?><?php else: ?><?= e($display['message']) ?><?php endif; ?></div>
    <p class="status-meta">실시간 업데이트 중…</p>
    <p class="car-code-ref">차량 코드 <code class="car-code"><?= e($carCode) ?></code></p>
</section>

<section class="card guestbook" id="guestbook">
    <h2>따뜻한 한마디 💬</h2>
    <p class="guestbook-desc">익명으로 응원 메시지를 남겨 주세요</p>

    <div class="quick-replies" role="group" aria-label="빠른 응원">
        <?php foreach (guest_quick_replies() as $reply): ?>
            <button type="button"
                    class="emoji-btn"
                    data-message="<?= e($reply['message']) ?>"
                    title="<?= e($reply['message']) ?>"
                    aria-label="<?= e($reply['message']) ?>"><?= e($reply['emoji']) ?></button>
        <?php endforeach; ?>
    </div>

    <form id="messageForm" class="message-form">
        <textarea name="message" rows="2" maxlength="200" placeholder="천천히 가셔도 돼요! 화이팅!" required></textarea>
        <button type="submit" class="btn btn-primary">보내기</button>
    </form>

    <ul class="message-list" id="messageList"></ul>
</section>

<section class="card report-section" id="reportSection">
    <button type="button" class="report-toggle" id="reportToggle" aria-expanded="false" aria-controls="reportPanel">
        문제가 있나요? 신고하기
    </button>
    <div class="report-panel" id="reportPanel" hidden>
        <p class="report-desc">부적절한 내용을 발견하셨다면 알려 주세요. 차량 코드 <code><?= e($carCode) ?></code>가 함께 전달됩니다.</p>
        <form id="reportForm" class="report-form">
            <label class="field">
                <span>신고 사유</span>
                <select name="reason" required>
                    <option value="">선택해 주세요</option>
                    <?php foreach (report_reasons() as $key => $label): ?>
                        <option value="<?= e($key) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>상세 내용 (선택)</span>
                <textarea name="detail" rows="2" maxlength="500" placeholder="어떤 문제인지 간단히 적어 주세요"></textarea>
            </label>
            <button type="submit" class="btn btn-secondary btn-block">신고 접수</button>
        </form>
        <p class="form-hint" id="reportHint" hidden></p>
    </div>
</section>

<script>
window.ChoQPage = {
    mode: 'car',
    carCode: <?= json_encode($carCode, JSON_UNESCAPED_UNICODE) ?>,
    since: <?= json_encode($updatedAt, JSON_UNESCAPED_UNICODE) ?>,
    pollInterval: <?= (int) $poll_interval ?>
};
</script>
