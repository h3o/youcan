<?php
$currentUser = \App\Core\Auth::user();
$isOwner     = $currentUser && (int)$currentUser['id'] === (int)$story['user_id'];
$genres      = \App\Models\Story::decodeGenres($story['genres']);
?>
<div class="container container--narrow">
    <article class="story-reader">

        <header class="story-reader__header">
            <?php foreach ($genres as $g): ?>
                <span class="story-card__genre"><?= htmlspecialchars(t('genre.' . $g), ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
            <h1 class="story-reader__title"><?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="story-reader__meta">
                <a href="/profile/<?= htmlspecialchars($story['username'], ENT_QUOTES, 'UTF-8') ?>" class="story-reader__author">
                    <span class="avatar avatar--sm"><?= strtoupper(substr($story['username'], 0, 1)) ?></span>
                    <?= htmlspecialchars($story['username'], ENT_QUOTES, 'UTF-8') ?>
                </a>
                <span class="story-reader__date"><?= date('F j, Y', strtotime($story['created_at'])) ?></span>
                <span class="story-reader__views">· <?= (int)$story['view_count'] ?> <?= htmlspecialchars(t('story.views'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </header>

        <div class="story-reader__body ql-editor">
            <?= $story['content'] ?>
        </div>

        <footer class="story-reader__footer">
            <button
                class="like-btn<?= $userLiked ? ' like-btn--liked' : '' ?>"
                data-slug="<?= htmlspecialchars($story['slug'], ENT_QUOTES, 'UTF-8') ?>"
                data-liked="<?= $userLiked ? 'true' : 'false' ?>"
                aria-pressed="<?= $userLiked ? 'true' : 'false' ?>"
                aria-label="<?= htmlspecialchars(t('story.like_aria'), ENT_QUOTES, 'UTF-8') ?>"
                <?= !\App\Core\Auth::check() ? 'data-login-required="true"' : '' ?>>
                <span class="like-btn__icon">♥</span>
                <span class="like-btn__count"><?= (int)$likeCount ?></span>
            </button>

            <?php if ($isOwner): ?>
            <div class="story-reader__actions">
                <a href="/stories/<?= htmlspecialchars($story['slug'], ENT_QUOTES, 'UTF-8') ?>/edit"
                   class="btn btn--ghost btn--sm"><?= htmlspecialchars(t('story.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                <form method="post" action="/stories/<?= htmlspecialchars($story['slug'], ENT_QUOTES, 'UTF-8') ?>/delete"
                      class="delete-form"
                      onsubmit="return confirm(<?= htmlspecialchars(json_encode(t('story.delete_confirm')), ENT_QUOTES, 'UTF-8') ?>)">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn btn--danger btn--sm"><?= htmlspecialchars(t('story.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
            </div>
            <?php endif; ?>
        </footer>

    </article>
</div>
