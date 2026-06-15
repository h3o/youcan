<div class="container container--narrow">
    <div class="editor-page">
        <h1 class="editor-page__title"><?= htmlspecialchars(t('story.write_heading'), ENT_QUOTES, 'UTF-8') ?></h1>

        <form method="post" action="<?= url('/stories') ?>" class="form" id="story-form">
            <?= \App\Core\Csrf::field() ?>

            <div class="form-group<?= isset($errors['title']) ? ' form-group--error' : '' ?>">
                <label class="form-group__label" for="title"><?= htmlspecialchars(t('story.title_label'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-group__input form-group__input--title" type="text" id="title" name="title"
                       value="<?= htmlspecialchars($old['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="<?= htmlspecialchars(t('story.title_ph'), ENT_QUOTES, 'UTF-8') ?>"
                       required maxlength="255">
                <?php if (isset($errors['title'])): ?>
                    <span class="form-group__error"><?= htmlspecialchars($errors['title'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-group__label">
                    <?= htmlspecialchars(t('story.genre_label'), ENT_QUOTES, 'UTF-8') ?>
                    <span class="form-group__optional"><?= htmlspecialchars(t('story.genre_optional'), ENT_QUOTES, 'UTF-8') ?></span>
                </label>
                <p class="genre-prompt"><?= htmlspecialchars(t('story.genre_prompt'), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="genre-picker">
                    <?php foreach ($genres as $g): ?>
                    <label class="genre-chip">
                        <input type="checkbox" name="genres[]" value="<?= htmlspecialchars($g, ENT_QUOTES, 'UTF-8') ?>"
                               <?= in_array($g, $selectedGenres ?? [], true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars(t('genre.' . $g), ENT_QUOTES, 'UTF-8') ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-group__label" for="language">
                    <?= htmlspecialchars(t('story.language_label'), ENT_QUOTES, 'UTF-8') ?>
                    <span class="form-group__optional"><?= htmlspecialchars(t('story.language_optional'), ENT_QUOTES, 'UTF-8') ?></span>
                </label>
                <select class="form-group__input form-group__select" id="language" name="language">
                    <option value=""><?= htmlspecialchars(t('story.language_ph'), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php foreach (['sk','cs','en','de','pl','hu','uk','fr','es','it','other'] as $code): ?>
                    <option value="<?= $code ?>" <?= ($old['language'] ?? '') === $code ? 'selected' : '' ?>>
                        <?= htmlspecialchars(t('lang.' . $code), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-group__label"><?= htmlspecialchars(t('story.vis_label'), ENT_QUOTES, 'UTF-8') ?></label>
                <div class="vis-picker">
                    <?php foreach (['public','members','secret'] as $v): ?>
                    <label class="vis-option <?= ($old['visibility'] ?? 'public') === $v ? 'vis-option--active' : '' ?>">
                        <input type="radio" name="visibility" value="<?= $v ?>"
                               <?= ($old['visibility'] ?? 'public') === $v ? 'checked' : '' ?>>
                        <span class="vis-option__label"><?= htmlspecialchars(t('story.vis_' . $v), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="vis-option__hint"><?= htmlspecialchars(t('story.vis_' . $v . '_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group<?= isset($errors['content']) ? ' form-group--error' : '' ?>">
                <label class="form-group__label"><?= htmlspecialchars(t('story.content_label'), ENT_QUOTES, 'UTF-8') ?></label>
                <div id="quill-editor" class="quill-editor"
                     data-placeholder="<?= htmlspecialchars(t('story.content_ph'), ENT_QUOTES, 'UTF-8') ?>"></div>
                <input type="hidden" name="content" id="content-input"
                       value="<?= htmlspecialchars($old['content'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <?php if (isset($errors['content'])): ?>
                    <span class="form-group__error"><?= htmlspecialchars($errors['content'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary"><?= htmlspecialchars(t('story.publish'), ENT_QUOTES, 'UTF-8') ?></button>
                <a href="<?= url('/') ?>" class="btn btn--ghost"><?= htmlspecialchars(t('story.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </form>
    </div>
</div>
