<div class="auth-page">
    <div class="auth-card">
        <h1 class="auth-card__title"><?= htmlspecialchars(t('auth.create_account'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="auth-card__subtitle"><?= htmlspecialchars(t('auth.subtitle_register', ['site' => t('site.name')]), ENT_QUOTES, 'UTF-8') ?></p>

        <form method="post" action="<?= url('/register') ?>" class="form" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="form-group<?= isset($errors['username']) ? ' form-group--error' : '' ?>">
                <label class="form-group__label" for="username"><?= htmlspecialchars(t('auth.username'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-group__input" type="text" id="username" name="username"
                       value="<?= htmlspecialchars($old['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       autocomplete="username" required maxlength="30">
                <?php if (isset($errors['username'])): ?>
                    <span class="form-group__error"><?= htmlspecialchars($errors['username'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group<?= isset($errors['email']) ? ' form-group--error' : '' ?>">
                <label class="form-group__label" for="email"><?= htmlspecialchars(t('auth.email'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-group__input" type="email" id="email" name="email"
                       value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       autocomplete="email" required>
                <?php if (isset($errors['email'])): ?>
                    <span class="form-group__error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group<?= isset($errors['password']) ? ' form-group--error' : '' ?>">
                <label class="form-group__label" for="password"><?= htmlspecialchars(t('auth.password'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-group__input" type="password" id="password" name="password"
                       autocomplete="new-password" required minlength="8">
                <?php if (isset($errors['password'])): ?>
                    <span class="form-group__error"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group<?= isset($errors['password_confirm']) ? ' form-group--error' : '' ?>">
                <label class="form-group__label" for="password_confirm"><?= htmlspecialchars(t('auth.confirm_password'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-group__input" type="password" id="password_confirm" name="password_confirm"
                       autocomplete="new-password" required>
                <?php if (isset($errors['password_confirm'])): ?>
                    <span class="form-group__error"><?= htmlspecialchars($errors['password_confirm'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn--primary btn--full"><?= htmlspecialchars(t('auth.register_btn'), ENT_QUOTES, 'UTF-8') ?></button>
        </form>

        <p class="auth-card__switch"><?= htmlspecialchars(t('auth.have_account'), ENT_QUOTES, 'UTF-8') ?>
           <a href="<?= url('/login') ?>"><?= htmlspecialchars(t('auth.sign_in_link'), ENT_QUOTES, 'UTF-8') ?></a></p>
    </div>
</div>
