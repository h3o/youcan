<?php

namespace App\Helpers;

use App\Core\Database;

class Slug
{
    public static function generate(string $title, ?int $excludeId = null): string
    {
        $slug = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($slug));
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 200);

        if ($slug === '') {
            $slug = 'story';
        }

        $db   = Database::getInstance();
        $base = $slug;
        $suffix = 2;

        while (true) {
            $sql    = 'SELECT id FROM stories WHERE slug = ?' . ($excludeId ? ' AND id != ?' : '');
            $stmt   = $db->prepare($sql);
            $args   = $excludeId ? [$slug, $excludeId] : [$slug];
            $stmt->execute($args);

            if (!$stmt->fetch()) {
                break;
            }
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
