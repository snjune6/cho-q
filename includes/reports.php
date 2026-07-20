<?php

declare(strict_types=1);

/** @return array<string, string> */
function report_reasons(): array
{
    return [
        'inappropriate' => '부적절한 내용',
        'profanity'     => '욕설·혐오 표현',
        'spam'          => '스팸·광고',
        'other'         => '기타',
    ];
}

function report_reason_label(string $reason): string
{
    $reasons = report_reasons();
    return $reasons[$reason] ?? $reason;
}

function is_valid_report_reason(string $reason): bool
{
    return array_key_exists($reason, report_reasons());
}

function report_rate_limit(): int
{
    return max(1, (int) (app_config()['report_rate_limit'] ?? 3));
}

function report_rate_window_sec(): int
{
    return max(60, (int) (app_config()['report_rate_window_sec'] ?? 3600));
}

function assert_report_rate_limit(int $carId): void
{
    $ip = request_client_ip();
    if ($ip === null) {
        return;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM reports
         WHERE car_id = ? AND reporter_ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)'
    );
    $stmt->execute([$carId, $ip, report_rate_window_sec()]);
    $count = (int) $stmt->fetchColumn();

    if ($count >= report_rate_limit()) {
        throw new InvalidArgumentException('신고는 1시간에 ' . report_rate_limit() . '회까지 가능합니다.');
    }
}

function create_report(int $carId, string $reason, string $detail): int
{
    if (!is_valid_report_reason($reason)) {
        throw new InvalidArgumentException('유효하지 않은 신고 사유입니다.');
    }

    $detail = trim($detail);
    if (mb_strlen($detail) > 500) {
        throw new InvalidArgumentException('상세 내용은 500자 이하여야 합니다.');
    }

    if ($detail !== '') {
        assert_no_blocked_content($detail);
    }

    assert_report_rate_limit($carId);

    $stmt = db()->prepare(
        'INSERT INTO reports (car_id, reason, detail, reporter_ip, status) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$carId, $reason, $detail, request_client_ip(), 'pending']);

    return (int) db()->lastInsertId();
}

function find_pending_reports(int $limit = 30): array
{
    $limit = min(100, max(1, $limit));
    $stmt = db()->prepare(
        'SELECT r.id, r.car_id, r.reason, r.detail, r.reporter_ip, r.created_at,
                c.car_code, c.is_active
         FROM reports r
         INNER JOIN cars c ON c.id = r.car_id
         WHERE r.status = ?
         ORDER BY r.created_at DESC
         LIMIT ' . $limit
    );
    $stmt->execute(['pending']);

    return $stmt->fetchAll() ?: [];
}

function count_pending_reports(): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM reports WHERE status = ?');
    $stmt->execute(['pending']);

    return (int) $stmt->fetchColumn();
}

function resolve_report(int $reportId, int $adminUserId, bool $suspendCar = false): void
{
    $stmt = db()->prepare(
        'SELECT r.id, r.car_id, r.status, c.car_code
         FROM reports r
         INNER JOIN cars c ON c.id = r.car_id
         WHERE r.id = ?
         LIMIT 1'
    );
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if (!$report) {
        throw new InvalidArgumentException('신고를 찾을 수 없습니다.');
    }

    if ((string) $report['status'] !== 'pending') {
        throw new InvalidArgumentException('이미 처리된 신고입니다.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'UPDATE reports SET status = ?, resolved_by = ?, resolved_at = NOW() WHERE id = ?'
        );
        $stmt->execute(['resolved', $adminUserId, $reportId]);

        if ($suspendCar) {
            set_car_active((int) $report['car_id'], false);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
