<section class="card error-card">
    <div class="error-icon">😿</div>
    <h1><?= e($pageTitle) ?></h1>
    <p><?= e($message ?? '알 수 없는 오류가 발생했습니다.') ?></p>
    <a href="/" class="btn btn-primary">홈으로</a>
</section>
