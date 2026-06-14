<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Helpers\Purifier;
use App\Helpers\Slug;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Story;

class StoryController
{
    private const GENRES = [
        'Fantasy', 'Science Fiction', 'Horror', 'Romance',
        'Mystery', 'Thriller', 'Historical', 'Literary Fiction',
        'Adventure', 'Other',
    ];

    public function index(array $params): void
    {
        $stories = Story::getRecent(20);
        View::render('stories/index', [
            'pageTitle' => t('site.name'),
            'stories'   => $stories,
        ]);
    }

    public function create(array $params): void
    {
        View::render('stories/create', [
            'pageTitle' => t('story.write_heading'),
            'genres'    => self::GENRES,
        ]);
    }

    public function store(array $params): void
    {
        Csrf::validate($_POST['_csrf'] ?? '') || Csrf::fail();

        $title   = trim($_POST['title'] ?? '');
        $genres  = $this->filterGenres($_POST['genres'] ?? []);
        $content = $_POST['content'] ?? '';

        $errors = $this->validateStory($title, $genres, $content);

        if ($errors) {
            View::render('stories/create', [
                'pageTitle'      => t('story.write_heading'),
                'genres'         => self::GENRES,
                'errors'         => $errors,
                'old'            => ['title' => $title, 'content' => $content],
                'selectedGenres' => $genres,
            ]);
            return;
        }

        $clean = Purifier::sanitize($content);
        $slug  = Slug::generate($title);

        Story::create(Auth::id(), $title, $slug, $clean, $genres);
        Response::redirect('/stories/' . $slug);
    }

    public function show(array $params): void
    {
        $story = Story::findBySlug($params['slug']);
        if (!$story) {
            Response::abort(404);
        }

        Story::incrementViews($story['id']);

        $likeCount = Like::countForStory($story['id']);
        $userLiked = Auth::check() ? Like::userLiked(Auth::id(), $story['id']) : false;

        // Fetch comments and re-resolve inline anchors
        $grouped    = Comment::getForStory((int)$story['id']);
        $inlineComments  = $grouped['inline'];
        $storyComments   = $grouped['story'];
        $detachedComments = $grouped['detached'];
        $this->resolveCommentAnchors($story['content'], $inlineComments);

        View::render('stories/show', [
            'pageTitle'        => View::e($story['title']) . ' — ' . t('site.name'),
            'story'            => $story,
            'likeCount'        => $likeCount,
            'userLiked'        => $userLiked,
            'inlineComments'   => $inlineComments,
            'storyComments'    => $storyComments,
            'detachedComments' => $detachedComments,
        ]);
    }

    public function edit(array $params): void
    {
        $story = Story::findBySlug($params['slug']);
        if (!$story) {
            Response::abort(404);
        }
        if ((int)$story['user_id'] !== Auth::id()) {
            Response::abort(403);
        }

        View::render('stories/edit', [
            'pageTitle'      => t('story.edit_heading'),
            'story'          => $story,
            'genres'         => self::GENRES,
            'selectedGenres' => Story::decodeGenres($story['genres']),
        ]);
    }

    public function update(array $params): void
    {
        Csrf::validate($_POST['_csrf'] ?? '') || Csrf::fail();

        $story = Story::findBySlug($params['slug']);
        if (!$story) {
            Response::abort(404);
        }
        if ((int)$story['user_id'] !== Auth::id()) {
            Response::abort(403);
        }

        $title   = trim($_POST['title'] ?? '');
        $genres  = $this->filterGenres($_POST['genres'] ?? []);
        $content = $_POST['content'] ?? '';

        $errors = $this->validateStory($title, $genres, $content);

        if ($errors) {
            View::render('stories/edit', [
                'pageTitle'      => t('story.edit_heading'),
                'story'          => array_merge($story, ['title' => $title, 'content' => $content]),
                'genres'         => self::GENRES,
                'selectedGenres' => $genres,
                'errors'         => $errors,
            ]);
            return;
        }

        $clean   = Purifier::sanitize($content);
        $newSlug = Slug::generate($title, (int)$story['id']);

        Story::update($story['id'], $title, $newSlug, $clean, $genres);
        Response::redirect('/stories/' . $newSlug);
    }

    public function destroy(array $params): void
    {
        Csrf::validate($_POST['_csrf'] ?? '') || Csrf::fail();

        $story = Story::findBySlug($params['slug']);
        if (!$story) {
            Response::abort(404);
        }
        if ((int)$story['user_id'] !== Auth::id()) {
            Response::abort(403);
        }

        Story::delete($story['id']);
        Session::flash('success', t('flash.deleted'));
        Response::redirect('/');
    }

    private function filterGenres($input): array
    {
        if (!is_array($input)) {
            return [];
        }
        $genres = self::GENRES;
        return array_values(array_filter($input, function ($g) use ($genres) {
            return in_array($g, $genres, true);
        }));
    }

    private function resolveCommentAnchors(string $html, array &$comments): void
    {
        if (empty($comments)) {
            return;
        }
        $plain = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');

        foreach ($comments as &$c) {
            $needle = $c['anchor_text'] ?? '';
            if (!$needle) {
                continue;
            }

            $positions = [];
            $offset    = 0;
            while (($pos = mb_strpos($plain, $needle, $offset)) !== false) {
                $positions[] = $pos;
                $offset = $pos + 1;
            }

            if (empty($positions)) {
                $c['anchor_detached'] = 1;
                Comment::markDetached((int)$c['id']);
                continue;
            }

            $best = 0;
            if (count($positions) > 1) {
                $bestScore = -1;
                $len       = mb_strlen($needle);
                foreach ($positions as $i => $pos) {
                    $pre = mb_substr($plain, max(0, $pos - 100), 100);
                    $suf = mb_substr($plain, $pos + $len, 100);
                    similar_text($pre, $c['anchor_prefix'] ?? '', $pct1);
                    similar_text($suf, $c['anchor_suffix'] ?? '', $pct2);
                    if (($pct1 + $pct2) > $bestScore) {
                        $bestScore = $pct1 + $pct2;
                        $best = $i;
                    }
                }
            }
            $c['occurrence_idx'] = $best;
        }
        unset($c);
    }

    private function validateStory(string $title, array $genres, string $content): array
    {
        $errors = [];
        if ($title === '' || strlen($title) > 255) {
            $errors['title'] = t('error.title_length');
        }
        if (trim(strip_tags($content)) === '') {
            $errors['content'] = t('error.content_empty');
        }
        return $errors;
    }
}
