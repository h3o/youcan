<?php

namespace App\Models;

use App\Core\Database;

class Comment
{
    /**
     * Fetch all top-level comments for a story (with replies nested),
     * separated by type. Returns ['inline' => [...], 'story' => [...]].
     * Each top-level row has a 'replies' key with an array of reply rows.
     */
    public static function getForStory(int $storyId): array
    {
        $db = Database::getInstance();

        // Top-level comments
        $stmt = $db->prepare(
            'SELECT c.*, u.username
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.story_id = ? AND c.parent_id IS NULL
             ORDER BY c.created_at ASC'
        );
        $stmt->execute([$storyId]);
        $tops = $stmt->fetchAll();

        if (empty($tops)) {
            return ['inline' => [], 'story' => [], 'detached' => []];
        }

        $topIds = array_column($tops, 'id');
        $placeholders = implode(',', array_fill(0, count($topIds), '?'));

        $stmt = $db->prepare(
            "SELECT c.*, u.username
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.parent_id IN ($placeholders)
             ORDER BY c.created_at ASC"
        );
        $stmt->execute($topIds);
        $replies = $stmt->fetchAll();

        // Group replies by parent_id
        $replyMap = [];
        foreach ($replies as $r) {
            $replyMap[(int)$r['parent_id']][] = $r;
        }

        // Attach replies and split by type/detached
        $inline   = [];
        $story    = [];
        $detached = [];

        foreach ($tops as $c) {
            $c['replies'] = $replyMap[(int)$c['id']] ?? [];
            // Cast to int so PHP 7.3 PDO string "0"/"1" becomes 0/1 in JSON
            $c['anchor_detached'] = (int)$c['anchor_detached'];
            $c['occurrence_idx']  = (int)$c['occurrence_idx'];
            if ($c['type'] === 'inline') {
                if ($c['anchor_detached']) {
                    $detached[] = $c;
                } else {
                    $inline[] = $c;
                }
            } else {
                $story[] = $c;
            }
        }

        return ['inline' => $inline, 'story' => $story, 'detached' => $detached];
    }

    public static function create(array $data): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO comments
             (story_id, user_id, parent_id, type,
              anchor_text, anchor_prefix, anchor_suffix, anchor_start, occurrence_idx,
              body)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['story_id'],
            $data['user_id'],
            $data['parent_id']     ?? null,
            $data['type']          ?? 'story',
            $data['anchor_text']   ?? null,
            $data['anchor_prefix'] ?? null,
            $data['anchor_suffix'] ?? null,
            $data['anchor_start']  ?? null,
            $data['occurrence_idx'] ?? 0,
            $data['body'],
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM comments WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM comments WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function markDetached(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE comments SET anchor_detached = 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Compute which occurrence (0-based) of $needle in $plainText
     * best matches the given prefix/suffix context.
     */
    public static function computeOccurrenceIdx(
        string $plainText,
        string $needle,
        string $prefix = '',
        string $suffix = ''
    ): int {
        $positions = [];
        $offset    = 0;
        while (($pos = mb_strpos($plainText, $needle, $offset)) !== false) {
            $positions[] = $pos;
            $offset = $pos + 1;
        }

        if (empty($positions)) {
            return 0;
        }
        if (count($positions) === 1) {
            return 0;
        }

        $best      = 0;
        $bestScore = -1;
        $len       = mb_strlen($needle);

        foreach ($positions as $i => $pos) {
            $pre   = mb_substr($plainText, max(0, $pos - 100), 100);
            $suf   = mb_substr($plainText, $pos + $len, 100);
            $score = 0;
            similar_text($pre, $prefix, $pct1);
            similar_text($suf, $suffix, $pct2);
            $score = $pct1 + $pct2;
            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $i;
            }
        }
        return $best;
    }
}
