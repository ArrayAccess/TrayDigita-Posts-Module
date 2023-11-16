<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Singular;

use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\Post;
use ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Abstracts\SinglePostAbstract;
use ArrayAccess\TrayDigita\Exceptions\InvalidArgument\InvalidArgumentException;
use function is_int;
use function sprintf;

class TypePost extends SinglePostAbstract
{
    protected string $postType = Post::TYPE_POST;
    private bool $postInit = false;

    public function getPost(): ?Post
    {
        if ($this->postInit) {
            return $this->post;
        }
        /** @noinspection DuplicatedCode */
        if ($this->post) {
            return $this->post;
        }
        $this->postInit = true;
        $this->post = null;
        $post = is_int($this->identity)
            ? $this->module->findPostById($this->identity)
            : $this->module->findPostBySlug($this->identity);
        if (!$post) {
            return $this->post;
        }
        if ($post->getNormalizeType() === $this->postType) {
            $this->post = $post;
        } elseif ($post->isRevision()
            && $post->getParent()->getNormalizeType() === $this->postType
        ) {
            $this->post = $post;
        }

        return $this->post;
    }

    protected function assertPost(Post $post): void
    {
        $type = $post->getNormalizeType();
        if ($type === $this->postType) {
            return;
        }
        $className = match ($type) {
            Post::TYPE_REVISION => TypeRevision::class,
            Post::TYPE_PAGE => TypePage::class,
            Post::TYPE_POST => TypePost::class,
            default => TypeCustom::class,
        };
        throw new InvalidArgumentException(
            sprintf(
                'Invalid post type using %s. The object should used %s',
                $this::class,
                $className
            )
        );
    }
}
