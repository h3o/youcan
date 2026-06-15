<?php

namespace App\Controllers;

use App\Core\Auth;
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

        $currentUser = Auth::user();
        $isOwn       = $currentUser && (int)$currentUser['id'] === (int)$user['id'];
        $stories     = Story::getByUser((int)$user['id'], $isOwn, Auth::check());

        View::render('profile/show', [
            'pageTitle'  => View::e($user['username']) . ' — YouCan',
            'profileUser' => $user,
            'stories'    => $stories,
        ]);
    }
}
