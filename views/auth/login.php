<div class="auth-page">
    <div class="auth-card">
        <h1 class="auth-card__title"><?= htmlspecialchars(t('auth.welcome_back'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="auth-card__subtitle"><?= htmlspecialchars(t('auth.subtitle_login'), ENT_QUOTES, 'UTF-8') ?></p>

        <?php if (isset($errors['form'])): ?>
            <div class="flash flash--error"><?= htmlspecialchars($errors['form'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="/login" class="form" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="form-group">
                <label class="form-group__label" for="identifier"><?= htmlspecialchars(t('auth.identifier'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-group__input" type="text" id="identifier" name="identifier"
                       value="<?= htmlspecialchars($old['identifier'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       autocomplete="username" required>
            </div>

            <div class="form-group">
                <label class="form-group__label" for="password"><?= htmlspecialchars(t('auth.password'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-group__input" type="password" id="password" name="password"
                       autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn--primary btn--full"><?= htmlspecialchars(t('auth.login_btn'), ENT_QUOTES, 'UTF-8') ?></button>
        </form>

        <p class="auth-card__switch"><?= htmlspecialchars(t('auth.no_account'), ENT_QUOTES, 'UTF-8') ?>
           <a href="/register"><?= htmlspecialchars(t('auth.join_link', ['site' => t('site.name')]), ENT_QUOTES, 'UTF-8') ?></a></p>
    </div>
</div>
