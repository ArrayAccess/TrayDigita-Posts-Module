<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Abstracts;

use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\Post;
use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\PostCategory;
use ArrayAccess\TrayDigita\App\Modules\Posts\Posts;

/**
 * @mixin Post
 */
abstract class SinglePostAbstract extends AbstractPostType
{
    public readonly int|string $identity;

    public function __construct(
        Posts $module,
        bool $publicArea,
        int|string|Post $identity
    ) {
        if ($identity instanceof Post) {
            $this->assertPost($identity);
            $this->post = $identity;
            $identity = $this->post->getId();
        }
        $this->identity = $identity;
        parent::__construct($module, $publicArea);
    }

    protected function assertPost(Post $post): void
    {
    }

    public function isFound(): bool
    {
        return $this->getPost() !== null;
    }

    public function getType(): string
    {
        return self::TYPE_SINGULAR;
    }

    public function getPostCategory(): ?PostCategory
    {
        return $this->getPost()?->getCategory();
    }

    public function __get(string $name)
    {
        if (parent::__isset($name)) {
            return $this->get($name);
        }
        return $this->getPost()?->$name;
    }

    public function __isset(string $name): bool
    {
        return parent::__isset($name) || isset($this->getPost()?->$name);
    }

    public function __call(string $name, array $arguments)
    {
        return $this->getPost()?->$name(...$arguments);
    }
}
