<?php

namespace App\Controllers;

use App\Core\Response;
use App\Core\View;
use App\Models\Story;
use App\Models\User;

class ProfileController
{
    public function show(array $params): void
    {
        $user = User::findByUsername($params['username']);
        if (!$user) {
            Response::abort(404);
        }

        $stories = Story::getByUser((int)$user['id']);

        View::render('profile/show', [
            'pageTitle'  => View::e($user['username']) . ' — YouCan',
            'profileUser' => $user,
            'stories'    => $stories,
        ]);
    }
}
