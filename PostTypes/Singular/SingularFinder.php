<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Singular;

use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\Post;
use ArrayAccess\TrayDigita\App\Modules\Posts\Posts;
use ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Abstracts\SinglePostAbstract;
use function is_int;

class SingularFinder
{
    public static function create(
        Posts $posts,
        bool $publicArea,
        int|string $identity
    ): ?SinglePostAbstract {
        $post = is_int($identity)
            ? $posts->findPostById($identity)
            : $posts->findPostBySlug($identity);
        if (!$post) {
            return null;
        }
        return match ($post->getNormalizeType()) {
            Post::TYPE_POST => new TypePost($posts, $publicArea, $post),
            Post::TYPE_PAGE => new TypePage($posts, $publicArea, $post),
            Post::TYPE_REVISION => new TypeRevision($posts, $publicArea, $post),
            default => new TypeCustom($posts, $publicArea, $post)
        };
    }
}
