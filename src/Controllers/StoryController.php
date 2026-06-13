<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Helpers\Purifier;
use App\Helpers\Slug;
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

        View::render('stories/show', [
            'pageTitle' => View::e($story['title']) . ' — ' . t('site.name'),
            'story'     => $story,
            'likeCount' => $likeCount,
            'userLiked' => $userLiked,
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

    private function filterGenres(mixed $input): array
    {
        if (!is_array($input)) {
            return [];
        }
        return array_values(array_filter($input, fn($g) => in_array($g, self::GENRES, true)));
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
