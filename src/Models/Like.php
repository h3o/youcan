<?php

namespace App\Models;

use App\Core\Database;

class Like
{
    public static function toggle(int $userId, int $storyId): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('INSERT IGNORE INTO likes (user_id, story_id) VALUES (?, ?)');
        $stmt->execute([$userId, $storyId]);

        if ($stmt->rowCount() === 0) {
            $del = $db->prepare('DELETE FROM likes WHERE user_id = ? AND story_id = ?');
            $del->execute([$userId, $storyId]);
            return false;
        }
        return true;
    }

    public static function countForStory(int $storyId): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT COUNT(*) FROM likes WHERE story_id = ?');
        $stmt->execute([$storyId]);
        return (int)$stmt->fetchColumn();
    }

    public static function userLiked(int $userId, int $storyId): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT 1 FROM likes WHERE user_id = ? AND story_id = ?');
        $stmt->execute([$userId, $storyId]);
        return (bool)$stmt->fetchColumn();
    }
}
