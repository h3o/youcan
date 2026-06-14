<div class="hero">
    <div class="hero__inner">
        <h1 class="hero__title"><?= htmlspecialchars(t('home.hero_title'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="hero__sub"><?= htmlspecialchars(t('home.hero_sub'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (!\App\Core\Auth::check()): ?>
            <a href="<?= url('/register') ?>" class="btn btn--primary btn--large"><?= htmlspecialchars(t('home.start_writing'), ENT_QUOTES, 'UTF-8') ?></a>
        <?php else: ?>
            <a href="<?= url('/stories/create') ?>" class="btn btn--primary btn--large"><?= htmlspecialchars(t('home.write_story'), ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <?php if (empty($stories)): ?>
        <div class="empty-state">
            <p><?= htmlspecialchars(t('home.no_stories'), ENT_QUOTES, 'UTF-8') ?>
               <a href="<?= url('/register') ?>"><?= htmlspecialchars(t('home.be_first'), ENT_QUOTES, 'UTF-8') ?></a></p>
        </div>
    <?php else: ?>
        <div class="story-grid">
            <?php foreach ($stories as $story): ?>
            <article class="story-card">
                <div class="story-card__body">
                    <?php foreach (\App\Models\Story::decodeGenres($story['genres']) as $g): ?>
                        <span class="story-card__genre"><?= htmlspecialchars(t('genre.' . $g), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                    <h2 class="story-card__title">
                        <a href="<?= url('/stories/' . htmlspecialchars($story['slug'], ENT_QUOTES, 'UTF-8')) ?>">
                            <?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </h2>
                    <div class="story-card__meta">
                        <a href="<?= url('/profile/' . htmlspecialchars($story['username'], ENT_QUOTES, 'UTF-8')) ?>" class="story-card__author">
                            <?= htmlspecialchars($story['username'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <span class="story-card__date"><?= date('M j, Y', strtotime($story['created_at'])) ?></span>
                    </div>
                </div>
                <div class="story-card__footer">
                    <span class="story-card__views">👁 <?= (int)$story['view_count'] ?> <?= htmlspecialchars(t('story.views'), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="story-card__likes">♥ <?= (int)$story['like_count'] ?></span>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
