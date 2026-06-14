<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT id, username, email, password, bio, created_at FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT id, username, email, password, bio, created_at FROM users WHERE username = ?');
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT id, username, email, bio, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $username, string $email, string $password): int
    {
        $db   = Database::getInstance();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $hash]);
        return (int)$db->lastInsertId();
    }
}
