<?php

namespace App\Models;

use App\Core\Database;

class Story
{
    public static function getRecent(int $limit = 20, bool $memberAccess = false): array
    {
        $db = Database::getInstance();
        $visFilter = $memberAccess
            ? "s.visibility IN ('public','members')"
            : "s.visibility = 'public'";
        $stmt = $db->prepare(
            "SELECT s.id, s.slug, s.title, s.genres, s.language, s.visibility, s.view_count, s.created_at,
                    u.username, u.id AS user_id,
                    COUNT(l.story_id) AS like_count
             FROM stories s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN likes l ON l.story_id = s.id
             WHERE {$visFilter}
             GROUP BY s.id
             ORDER BY s.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function getByUser(int $userId, bool $isOwn = false, bool $memberAccess = false): array
    {
        $db = Database::getInstance();
        if ($isOwn) {
            $visFilter = '';
        } elseif ($memberAccess) {
            $visFilter = "AND s.visibility IN ('public','members')";
        } else {
            $visFilter = "AND s.visibility = 'public'";
        }
        $stmt = $db->prepare(
            "SELECT s.id, s.slug, s.title, s.genres, s.language, s.visibility, s.view_count, s.created_at,
                    COUNT(l.story_id) AS like_count
             FROM stories s
             LEFT JOIN likes l ON l.story_id = s.id
             WHERE s.user_id = ? {$visFilter}
             GROUP BY s.id
             ORDER BY s.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT s.*, u.username
             FROM stories s
             JOIN users u ON u.id = s.user_id
             WHERE s.slug = ?'
        );
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT s.*, u.username FROM stories s JOIN users u ON u.id = s.user_id WHERE s.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(
        int $userId, string $title, string $slug, string $content, array $genres,
        ?string $language = null, string $visibility = 'public', ?string $secretToken = null
    ): int {
        $db        = Database::getInstance();
        $genreJson = $genres ? json_encode(array_values($genres)) : null;
        $stmt      = $db->prepare(
            'INSERT INTO stories (user_id, title, slug, content, genres, language, visibility, secret_token)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $slug, $content, $genreJson, $language, $visibility, $secretToken]);
        return (int)$db->lastInsertId();
    }

    public static function update(
        int $id, string $title, string $slug, string $content, array $genres,
        ?string $language = null, string $visibility = 'public', ?string $secretToken = null
    ): void {
        $db        = Database::getInstance();
        $genreJson = $genres ? json_encode(array_values($genres)) : null;
        $stmt      = $db->prepare(
            'UPDATE stories SET title = ?, slug = ?, content = ?, genres = ?,
             language = ?, visibility = ?, secret_token = ? WHERE id = ?'
        );
        $stmt->execute([$title, $slug, $content, $genreJson, $language, $visibility, $secretToken, $id]);
    }

    public static function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM stories WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function incrementViews(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE stories SET view_count = view_count + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function generateSecretToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function decodeGenres(?string $json): array
    {
        if (!$json) {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
