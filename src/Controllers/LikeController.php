<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Models\Like;
use App\Models\Story;

class LikeController
{
    public function toggle(array $params): void
    {
        if (!Auth::check()) {
            Response::json(['error' => 'Unauthenticated'], 401);
        }

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        Csrf::validate($csrfToken) || Response::json(['error' => 'Invalid CSRF token'], 419);

        $story = Story::findBySlug($params['slug']);
        if (!$story) {
            Response::json(['error' => 'Not found'], 404);
        }

        $userId  = Auth::id();
        $storyId = (int)$story['id'];

        $liked = Like::toggle($userId, $storyId);
        $count = Like::countForStory($storyId);

        Response::json(['liked' => $liked, 'count' => $count]);
    }
}
