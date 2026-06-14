<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Story;

$currentUser = Auth::user();
$isOwner     = $currentUser && (int)$currentUser['id'] === (int)$story['user_id'];
$genres      = Story::decodeGenres($story['genres']);
$slug        = htmlspecialchars($story['slug'], ENT_QUOTES, 'UTF-8');

// Helper: render a single comment row (top-level or reply)
function renderComment(array $c, bool $isReply, string $slug, bool $isStoryOwner): void
{
    $currentUser = Auth::user();
    $isOwner = $currentUser && (
        (int)$currentUser['id'] === (int)$c['user_id']
        || $isStoryOwner
    );
    $initial = strtoupper(substr($c['username'], 0, 1));
    $cls = 'comment' . ($isReply ? ' comment--reply' : '');
    ?>
    <div class="<?= $cls ?>" id="comment-<?= (int)$c['id'] ?>">
        <div class="comment__header">
            <span class="avatar avatar--xs"><?= $initial ?></span>
            <a href="/profile/<?= htmlspecialchars($c['username'], ENT_QUOTES, 'UTF-8') ?>" class="comment__author">
                <?= htmlspecialchars($c['username'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <span class="comment__date"><?= date('M j, Y', strtotime($c['created_at'])) ?></span>
            <?php if ($isOwner): ?>
            <form method="post" action="/comments/<?= (int)$c['id'] ?>/delete" class="comment__delete-form"
                  onsubmit="return confirm(<?= htmlspecialchars(json_encode(t('comment.delete_confirm')), ENT_QUOTES, 'UTF-8') ?>)">
                <?= Csrf::field() ?>
                <button type="submit" class="comment__delete" title="<?= htmlspecialchars(t('comment.delete'), ENT_QUOTES, 'UTF-8') ?>">×</button>
            </form>
            <?php endif; ?>
        </div>
        <div class="comment__body"><?= htmlspecialchars($c['body'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php
}
?>

<div class="container container--narrow">
<div class="story-layout">
  <div class="story-layout__body">

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
            <p class="story-reader__hint"><?= htmlspecialchars(t('comment.select_hint'), ENT_QUOTES, 'UTF-8') ?></p>
        </header>

        <div class="story-reader__wrap">
            <div id="story-body" class="story-reader__body ql-editor"><?= $story['content'] ?></div>
            <div class="annotation-margin" id="annotation-margin" aria-hidden="true"></div>
        </div>

        <footer class="story-reader__footer">
            <button
                class="like-btn<?= $userLiked ? ' like-btn--liked' : '' ?>"
                data-slug="<?= $slug ?>"
                data-liked="<?= $userLiked ? 'true' : 'false' ?>"
                aria-pressed="<?= $userLiked ? 'true' : 'false' ?>"
                aria-label="<?= htmlspecialchars(t('story.like_aria'), ENT_QUOTES, 'UTF-8') ?>"
                <?= !Auth::check() ? 'data-login-required="true"' : '' ?>>
                <span class="like-btn__icon">♥</span>
                <span class="like-btn__count"><?= (int)$likeCount ?></span>
            </button>

            <?php if ($isOwner): ?>
            <div class="story-reader__actions">
                <a href="/stories/<?= $slug ?>/edit" class="btn btn--ghost btn--sm"><?= htmlspecialchars(t('story.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                <form method="post" action="/stories/<?= $slug ?>/delete" class="delete-form"
                      onsubmit="return confirm(<?= htmlspecialchars(json_encode(t('story.delete_confirm')), ENT_QUOTES, 'UTF-8') ?>)">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn--danger btn--sm"><?= htmlspecialchars(t('story.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
            </div>
            <?php endif; ?>
        </footer>
    </article>

    <!-- ── Detached (orphaned) annotations ─────────────────────────────── -->
    <?php if (!empty($detachedComments)): ?>
    <section class="comments-detached">
        <h3 class="comments-detached__heading">
            ⚠️ <?= htmlspecialchars(t('comment.detached_heading'), ENT_QUOTES, 'UTF-8') ?>
        </h3>
        <?php foreach ($detachedComments as $c): ?>
        <div class="comment comment--detached" id="comment-<?= (int)$c['id'] ?>">
            <div class="comment__anchor">
                <?php $detachedTip = htmlspecialchars(t('comment.detached_tooltip', ['text' => mb_substr($c['anchor_text'], 0, 80)]), ENT_QUOTES, 'UTF-8'); ?>
                <span class="comment__detached-icon" title="<?= $detachedTip ?>">⚠️</span>
                <em class="comment__anchor-quote">"<?= htmlspecialchars(mb_substr($c['anchor_text'], 0, 120), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($c['anchor_text']) > 120 ? '…' : '' ?>"</em>
            </div>
            <?php renderComment($c, false, $story['slug'], $isOwner); ?>
            <?php foreach ($c['replies'] as $r): renderComment($r, true, $story['slug'], $isOwner); endforeach; ?>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <!-- ── Whole-story comments ─────────────────────────────────────────── -->
    <section class="comments-section" id="comments">
        <h2 class="comments-section__heading"><?= htmlspecialchars(t('comment.heading'), ENT_QUOTES, 'UTF-8') ?></h2>

        <?php if (empty($storyComments)): ?>
            <p class="empty-state"><?= htmlspecialchars(t('comment.no_comments'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <?php foreach ($storyComments as $c): ?>
                <?php renderComment($c, false, $story['slug'], $isOwner); ?>
                <?php foreach ($c['replies'] as $r): renderComment($r, true, $story['slug'], $isOwner); endforeach; ?>

                <!-- Reply form for this comment -->
                <?php if (Auth::check()): ?>
                <form method="post" action="/stories/<?= $slug ?>/comments" class="comment-reply-form">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="type" value="story">
                    <input type="hidden" name="parent_id" value="<?= (int)$c['id'] ?>">
                    <textarea name="body" class="comment-reply-form__input"
                              placeholder="<?= htmlspecialchars(t('comment.reply_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                              rows="2" maxlength="2000"></textarea>
                    <button type="submit" class="btn btn--ghost btn--sm"><?= htmlspecialchars(t('comment.reply'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- New whole-story comment form -->
        <?php if (Auth::check()): ?>
        <form method="post" action="/stories/<?= $slug ?>/comments" class="comment-form" id="new-story-comment">
            <?= Csrf::field() ?>
            <input type="hidden" name="type" value="story">
            <div class="comment-form__row">
                <span class="avatar avatar--sm"><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></span>
                <div class="comment-form__fields">
                    <textarea name="body" class="form-group__input comment-form__input"
                              placeholder="<?= htmlspecialchars(t('comment.add_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                              rows="3" maxlength="2000" required></textarea>
                    <button type="submit" class="btn btn--primary btn--sm"><?= htmlspecialchars(t('comment.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </div>
        </form>
        <?php else: ?>
        <p class="comment-login-prompt">
            <a href="/login"><?= htmlspecialchars(t('comment.login_prompt'), ENT_QUOTES, 'UTF-8') ?></a>
        </p>
        <?php endif; ?>
    </section>

  </div><!-- /.story-layout__body -->

  <!-- ── Inline annotation side panel ───────────────────────────────────── -->
  <aside class="annotation-panel" id="annotation-panel" aria-hidden="true" aria-label="Annotation panel">
    <div class="annotation-panel__inner">
        <button class="annotation-panel__close" id="panel-close" aria-label="Close">×</button>
        <blockquote class="annotation-panel__quote" id="panel-quote"></blockquote>

        <div class="annotation-panel__thread" id="panel-thread"></div>

        <?php if (Auth::check()): ?>
        <form class="annotation-panel__form" id="panel-form" method="post" action="/stories/<?= $slug ?>/comments">
            <?= Csrf::field() ?>
            <input type="hidden" name="type" value="inline">
            <input type="hidden" name="anchor_text"   id="f-anchor-text">
            <input type="hidden" name="anchor_prefix" id="f-anchor-prefix">
            <input type="hidden" name="anchor_suffix" id="f-anchor-suffix">
            <input type="hidden" name="anchor_start"  id="f-anchor-start">
            <input type="hidden" name="parent_id"     id="f-parent-id">
            <textarea name="body" id="panel-body" class="annotation-panel__textarea"
                      placeholder="<?= htmlspecialchars(t('comment.add_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                      rows="3" maxlength="2000"></textarea>
            <div class="annotation-panel__actions">
                <button type="submit" class="btn btn--primary btn--sm"><?= htmlspecialchars(t('comment.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                <button type="button" class="btn btn--ghost btn--sm" id="panel-cancel"><?= htmlspecialchars(t('story.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>
        <?php else: ?>
        <p class="comment-login-prompt">
            <a href="/login"><?= htmlspecialchars(t('comment.login_prompt'), ENT_QUOTES, 'UTF-8') ?></a>
        </p>
        <?php endif; ?>
    </div>
  </aside>

</div><!-- /.story-layout -->
</div><!-- /.container -->

<!-- Floating selection bubble -->
<div class="annotation-bubble" id="annotation-bubble" aria-hidden="true">
    <button id="bubble-btn" type="button">💬 <?= htmlspecialchars(t('comment.add_note'), ENT_QUOTES, 'UTF-8') ?></button>
</div>

<!-- Inline comment data for JS -->
<script>
window.YOUCAN = {
    storySlug:  <?= json_encode($story['slug']) ?>,
    csrfToken:  <?= json_encode(Csrf::generate()) ?>,
    isLoggedIn: <?= Auth::check() ? 'true' : 'false' ?>,
    isOwner:    <?= $isOwner ? 'true' : 'false' ?>,
    comments:   <?= json_encode(array_values($inlineComments)) ?>,
    i18n: {
        reply:         <?= json_encode(t('comment.reply')) ?>,
        delete:        <?= json_encode(t('comment.delete')) ?>,
        deleteConfirm: <?= json_encode(t('comment.delete_confirm')) ?>,
        replyPh:       <?= json_encode(t('comment.reply_placeholder')) ?>,
        submit:        <?= json_encode(t('comment.submit')) ?>,
        cancel:        <?= json_encode(t('story.cancel')) ?>,
    }
};
</script>
