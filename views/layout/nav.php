<?php
use App\Core\Lang;
use App\Core\Session;
use App\Core\Auth;

$flashSuccess = Session::getFlash('success');
$flashError   = Session::getFlash('error');
$currentUser  = Auth::user();
$locale       = Lang::getLocale();

$activeLang = function (string $code) use ($locale) {
    return $locale === $code ? 'lang-switcher__btn--active' : '';
};
?>
<nav class="nav">
    <div class="nav__inner">
        <a href="<?= url('/') ?>" class="nav__logo"><?= htmlspecialchars(t('site.name'), ENT_QUOTES, 'UTF-8') ?></a>
        <button class="nav__toggle" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <ul class="nav__links">
            <li><a href="<?= url('/') ?>" class="nav__link"><?= t('nav.stories') ?></a></li>
            <?php if ($currentUser): ?>
                <li><a href="<?= url('/stories/create') ?>" class="nav__link nav__link--cta"><?= t('nav.write') ?></a></li>
                <li><a href="<?= url('/profile/' . htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8')) ?>" class="nav__link"><?= htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8') ?></a></li>
                <li>
                    <form method="post" action="<?= url('/logout') ?>" class="nav__logout-form">
                        <?= \App\Core\Csrf::field() ?>
                        <button type="submit" class="nav__link nav__link--logout"><?= t('nav.sign_out') ?></button>
                    </form>
                </li>
            <?php else: ?>
                <li><a href="<?= url('/login') ?>" class="nav__link"><?= t('nav.sign_in') ?></a></li>
                <li><a href="<?= url('/register') ?>" class="nav__link nav__link--cta"><?= t('nav.join') ?></a></li>
            <?php endif; ?>
            <li>
                <div class="lang-switcher">
                    <a href="<?= url('/lang/sk') ?>" class="lang-switcher__btn <?= $activeLang('sk') ?>">SK</a>
                    <a href="<?= url('/lang/cs') ?>" class="lang-switcher__btn <?= $activeLang('cs') ?>">CZ</a>
                    <a href="<?= url('/lang/en') ?>" class="lang-switcher__btn <?= $activeLang('en') ?>">EN</a>
                    <a href="<?= url('/lang/kl') ?>" class="lang-switcher__btn <?= $activeLang('kl') ?>">KL</a>
                </div>
            </li>
        </ul>
    </div>
</nav>

<?php if ($flashSuccess || $flashError): ?>
<div class="flash-container">
    <?php if ($flashSuccess): ?>
        <div class="flash flash--success"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="flash flash--error"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<main class="main">
