<div class="error-page">
    <h1 class="error-page__code">403</h1>
    <p class="error-page__message"><?= htmlspecialchars(t('error.forbidden_msg'), ENT_QUOTES, 'UTF-8') ?></p>
    <a href="<?= url('/') ?>" class="btn btn--primary"><?= htmlspecialchars(t('error.back_home'), ENT_QUOTES, 'UTF-8') ?></a>
</div>
