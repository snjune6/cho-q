<?php

declare(strict_types=1);

/** @var array $adminUser */
/** @var string $searchCode */
/** @var array|null $carDetail */
/** @var array $auditLogs */
/** @var array $pendingReports */
/** @var string|null $flashMessage */
/** @var string|null $flashError */

$presets = status_presets();
?>

<section class="card admin-intro">
    <h1>관리자 — QR 조회</h1>
    <p>문제 신고 QR의 소유 계정 확인·이용 중지</p>
    <p class="admin-user"><?= e($adminUser['email']) ?></p>
</section>

<?php if ($flashMessage): ?>
    <section class="card flash ok"><?= e($flashMessage) ?></section>
<?php endif; ?>

<?php if ($flashError): ?>
    <section class="card flash err"><?= e($flashError) ?></section>
<?php endif; ?>

<section class="card admin-reports">
    <h2>미처리 신고 <?php if ($pendingReports !== []): ?><span class="count-badge"><?= count($pendingReports) ?></span><?php endif; ?></h2>
    <?php if ($pendingReports === []): ?>
        <p class="empty-hint">접수된 미처리 신고가 없어요.</p>
    <?php else: ?>
        <ul class="report-list">
            <?php foreach ($pendingReports as $report): ?>
                <li class="report-item">
                    <div class="report-item-head">
                        <time datetime="<?= e((string) $report['created_at']) ?>"><?= e((string) $report['created_at']) ?></time>
                        <code><?= e((string) $report['car_code']) ?></code>
                    </div>
                    <p class="report-item-reason"><?= e(report_reason_label((string) $report['reason'])) ?></p>
                    <?php if (trim((string) ($report['detail'] ?? '')) !== ''): ?>
                        <p class="report-item-detail"><?= e((string) $report['detail']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($report['reporter_ip'])): ?>
                        <p class="report-item-ip">신고 IP <?= e((string) $report['reporter_ip']) ?></p>
                    <?php endif; ?>
                    <div class="admin-actions">
                        <a class="btn btn-secondary" href="/admin?car=<?= e(urlencode((string) $report['car_code'])) ?>">QR 조회</a>
                        <form method="post" action="/admin">
                            <input type="hidden" name="action" value="resolve_report">
                            <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                            <button type="submit" class="btn btn-secondary">처리 완료</button>
                        </form>
                        <form method="post" action="/admin">
                            <input type="hidden" name="action" value="resolve_report_suspend">
                            <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('신고 처리와 함께 이 QR 이용을 중지할까요?');">처리 + QR 중지</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="card">
    <h2>QR 코드 검색</h2>
    <form method="get" action="/admin" class="admin-search-form">
        <label class="field">
            <span>차량 코드 (car_code)</span>
            <input type="text" name="car" value="<?= e($searchCode) ?>" maxlength="32" placeholder="예: mybusan01">
        </label>
        <button type="submit" class="btn btn-primary">조회</button>
    </form>
</section>

<?php if ($carDetail): ?>
    <?php
    $isActive = (int) ($carDetail['is_active'] ?? 1) === 1;
    $statusKey = (string) ($carDetail['status_key'] ?? 'nervous');
    $statusLabel = $presets[$statusKey]['label'] ?? $statusKey;
    $messagePreview = custom_message_preview((string) ($carDetail['custom_message'] ?? ''));
    $ownerEmail = trim((string) ($carDetail['owner_email'] ?? ''));
    ?>
    <section class="card admin-detail">
        <h2>조회 결과</h2>
        <dl class="info-list">
            <dt>차량 코드</dt>
            <dd><code><?= e((string) $carDetail['car_code']) ?></code></dd>

            <dt>상태</dt>
            <dd>
                <?php if ($isActive): ?>
                    <span class="status-badge status-active">이용 중</span>
                <?php else: ?>
                    <span class="status-badge status-suspended">이용 중지</span>
                <?php endif; ?>
            </dd>

            <dt>소유 계정</dt>
            <dd>
                <?php if ($ownerEmail !== ''): ?>
                    <?= e((string) $carDetail['owner_name']) ?> &lt;<?= e($ownerEmail) ?>&gt;
                    <span class="role-badge role-<?= e((string) ($carDetail['owner_role'] ?? 'general')) ?>">
                        <?= e(user_role_label((string) ($carDetail['owner_role'] ?? 'general'))) ?>
                    </span>
                <?php else: ?>
                    <em>미연결 (데모/레거시)</em>
                <?php endif; ?>
            </dd>

            <dt>등록일</dt>
            <dd><?= e((string) $carDetail['created_at']) ?></dd>

            <dt>현재 운전자 상태</dt>
            <dd><?= e($statusLabel) ?></dd>

            <dt>표시 메시지</dt>
            <dd><?= e($messagePreview !== '' ? $messagePreview : '(없음)') ?></dd>

            <dt>마지막 업데이트</dt>
            <dd><?= e((string) ($carDetail['status_updated_at'] ?? '')) ?></dd>

            <dt>QR 페이지</dt>
            <dd><a href="<?= e(car_public_url((string) $carDetail['car_code'])) ?>" target="_blank" rel="noopener"><?= e(car_public_url((string) $carDetail['car_code'])) ?></a></dd>
        </dl>

        <div class="admin-actions">
            <form method="post" action="/admin?car=<?= e(urlencode((string) $carDetail['car_code'])) ?>">
                <input type="hidden" name="action" value="set_active">
                <input type="hidden" name="car_code" value="<?= e((string) $carDetail['car_code']) ?>">
                <?php if ($isActive): ?>
                    <input type="hidden" name="is_active" value="0">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('이 QR의 공개·방명록·콘솔 이용을 중지할까요?');">QR 이용 중지</button>
                <?php else: ?>
                    <input type="hidden" name="is_active" value="1">
                    <button type="submit" class="btn btn-primary">QR 이용 재개</button>
                <?php endif; ?>
            </form>
            <?php if ($isActive): ?>
                <a class="btn btn-secondary" href="<?= e(car_console_url((string) $carDetail['car_code'])) ?>">콘솔 열기</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="card admin-audit">
        <h2>상태 변경 이력</h2>
        <?php if ($auditLogs === []): ?>
            <p class="empty-hint">아직 기록된 변경 이력이 없어요. (이 기능 적용 후 콘솔에서 저장한 내역부터 남습니다)</p>
        <?php else: ?>
            <ul class="audit-log-list">
                <?php foreach ($auditLogs as $log): ?>
                    <?php
                    $logStatusKey = (string) ($log['status_key'] ?? '');
                    $logStatusLabel = $presets[$logStatusKey]['label'] ?? $logStatusKey;
                    $logPreview = custom_message_preview((string) ($log['custom_message'] ?? ''), 80);
                    $actorEmail = trim((string) ($log['actor_email'] ?? ''));
                    $actorName = trim((string) ($log['actor_name'] ?? ''));
                    ?>
                    <li class="audit-log-item">
                        <div class="audit-log-head">
                            <time datetime="<?= e((string) $log['created_at']) ?>"><?= e((string) $log['created_at']) ?></time>
                            <span class="audit-log-status"><?= e($logStatusLabel) ?></span>
                        </div>
                        <p class="audit-log-actor">
                            <?php if ($actorEmail !== ''): ?>
                                <?= e($actorName !== '' ? $actorName : $actorEmail) ?>
                                <?php if ($actorName !== ''): ?>&lt;<?= e($actorEmail) ?>&gt;<?php endif; ?>
                            <?php else: ?>
                                <em>알 수 없음</em>
                            <?php endif; ?>
                            <?php if (!empty($log['ip_address'])): ?>
                                <span class="audit-log-ip">IP <?= e((string) $log['ip_address']) ?></span>
                            <?php endif; ?>
                        </p>
                        <?php if ($logPreview !== ''): ?>
                            <p class="audit-log-message"><?= e($logPreview) ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="card">
    <a class="btn btn-secondary btn-block" href="/my">내 초큐로</a>
</section>
