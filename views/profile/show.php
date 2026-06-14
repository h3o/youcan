<div class="container">
    <div class="profile-header">
        <div class="avatar avatar--lg"><?= strtoupper(substr($profileUser['username'], 0, 1)) ?></div>
        <div class="profile-header__info">
            <h1 class="profile-header__name"><?= htmlspecialchars($profileUser['username'], ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if ($profileUser['bio']): ?>
                <p class="profile-header__bio"><?= htmlspecialchars($profileUser['bio'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <p class="profile-header__joined"><?= htmlspecialchars(t('profile.joined'), ENT_QUOTES, 'UTF-8') ?> <?= date('F Y', strtotime($profileUser['created_at'])) ?></p>
        </div>
    </div>

    <div class="profile-stories">
        <h2 class="profile-stories__heading"><?= htmlspecialchars(t('profile.stories_by'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($profileUser['username'], ENT_QUOTES, 'UTF-8') ?></h2>

        <?php if (empty($stories)): ?>
            <p class="empty-state"><?= htmlspecialchars(t('profile.no_stories'), ENT_QUOTES, 'UTF-8') ?></p>
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
                            <span class="story-card__date"><?= date('M j, Y', strtotime($story['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="story-card__footer">
                        <span class="story-card__views">👁 <?= (int)$story['view_count'] ?></span>
                        <span class="story-card__likes">♥ <?= (int)$story['like_count'] ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
