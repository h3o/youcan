<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Models\Comment;
use App\Models\Story;

class CommentController
{
    public function store(array $params): void
    {
        Auth::requireAuth();
        Csrf::validate($_POST['_csrf'] ?? '') || Csrf::fail();

        $story = Story::findBySlug($params['slug']);
        if (!$story) {
            Response::abort(404);
        }

        $body     = trim($_POST['body'] ?? '');
        $type     = in_array($_POST['type'] ?? '', ['story', 'inline']) ? $_POST['type'] : 'story';
        $parentId = isset($_POST['parent_id']) && is_numeric($_POST['parent_id'])
                    ? (int)$_POST['parent_id'] : null;

        if ($body === '' || mb_strlen($body) > 2000) {
            Response::redirect('/stories/' . $params['slug'] . '#comments');
        }

        // Enforce one-level threading
        if ($parentId !== null) {
            $parent = Comment::findById($parentId);
            if (!$parent
                || (int)$parent['story_id'] !== (int)$story['id']
                || $parent['parent_id'] !== null) {
                $parentId = null;
            }
            $type = $parent['type'] ?? 'story';
        }

        $data = [
            'story_id' => (int)$story['id'],
            'user_id'  => Auth::id(),
            'parent_id'=> $parentId,
            'type'     => $type,
            'body'     => $body,
        ];

        if ($type === 'inline' && $parentId === null) {
            $anchorText = trim($_POST['anchor_text'] ?? '');
            if ($anchorText === '') {
                Response::redirect('/stories/' . $params['slug']);
            }

            $plainText = html_entity_decode(strip_tags($story['content']), ENT_QUOTES, 'UTF-8');
            $prefix    = trim($_POST['anchor_prefix'] ?? '');
            $suffix    = trim($_POST['anchor_suffix'] ?? '');
            $start     = is_numeric($_POST['anchor_start'] ?? '') ? (int)$_POST['anchor_start'] : null;

            $occIdx = Comment::computeOccurrenceIdx($plainText, $anchorText, $prefix, $suffix);

            $data['anchor_text']    = $anchorText;
            $data['anchor_prefix']  = mb_substr($prefix, -200);
            $data['anchor_suffix']  = mb_substr($suffix, 0, 200);
            $data['anchor_start']   = $start;
            $data['occurrence_idx'] = $occIdx;
        }

        $id = Comment::create($data);
        Response::redirect('/stories/' . $params['slug'] . '#comment-' . $id);
    }

    public function destroy(array $params): void
    {
        Csrf::validate($_POST['_csrf'] ?? '') || Csrf::fail();

        $commentId = (int)($params['id'] ?? 0);
        $comment   = Comment::findById($commentId);
        if (!$comment) {
            Response::abort(404);
        }

        $story = Story::findById((int)$comment['story_id']);

        $isCommentOwner = Auth::id() === (int)$comment['user_id'];
        $isStoryAuthor  = $story && Auth::id() === (int)$story['user_id'];

        if (!$isCommentOwner && !$isStoryAuthor) {
            Response::abort(403);
        }

        Comment::delete($commentId);

        $slug = $story['slug'] ?? '';
        Response::redirect($slug ? '/stories/' . $slug . '#comments' : '/');
    }
}
