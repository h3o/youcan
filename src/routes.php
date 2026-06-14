<?php

use App\Controllers\AuthController;
use App\Controllers\CommentController;
use App\Controllers\LangController;
use App\Controllers\LikeController;
use App\Controllers\ProfileController;
use App\Controllers\StoryController;
use App\Core\Router;

$router = new Router();

// Language switcher
$router->get('/lang/{code}', [LangController::class, 'set']);

// Auth
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout'], auth: true);

// Stories — /stories/create must precede /stories/{slug}
$router->get('/stories/create', [StoryController::class, 'create'], auth: true);
$router->post('/stories', [StoryController::class, 'store'], auth: true);
$router->get('/stories/{slug}', [StoryController::class, 'show']);
$router->get('/stories/{slug}/edit', [StoryController::class, 'edit'], auth: true);
$router->post('/stories/{slug}/update', [StoryController::class, 'update'], auth: true);
$router->post('/stories/{slug}/delete', [StoryController::class, 'destroy'], auth: true);
$router->post('/stories/{slug}/like',     [LikeController::class,   'toggle'],  auth: true);
$router->post('/stories/{slug}/comments', [CommentController::class, 'store'],   auth: true);
$router->post('/comments/{id}/delete',    [CommentController::class, 'destroy'], auth: true);

// Profile
$router->get('/profile/{username}', [ProfileController::class, 'show']);

// Homepage (last — catch-all friendly)
$router->get('/', [StoryController::class, 'index']);

return $router;
