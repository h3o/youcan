<?php

namespace App\Core;

class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        static $user = null;
        if ($user === null) {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT id, username, email, bio, created_at FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
        }
        return $user;
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
            if ($isAjax) {
                Response::json(['error' => 'Unauthenticated'], 401);
            }
            Session::flash('_intended', $_SERVER['REQUEST_URI'] ?? '/');
            Response::redirect('/login');
        }
    }
}
