<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Abstracts;

use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\Post;
use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\PostCategory;
use ArrayAccess\TrayDigita\App\Modules\Posts\Posts;
use ArrayAccess\TrayDigita\Database\Helper\Expression;
use ArrayAccess\TrayDigita\Database\Result\LazyResultCriteria;
use Doctrine\Common\Collections\Criteria;

/**
 * @mixin PostCategory
 * @property-read ?LazyResultCriteria<Post> $posts
 */
abstract class CategoryPostAbstract extends ArchiveBasedPostAbstract
{
    private bool $categoryInit = false;

    public function __construct(
        Posts $module,
        bool $publicArea,
        public readonly int|string $identity
    ) {
        parent::__construct($module, $publicArea);
    }

    public function getType(): string
    {
        return self::TYPE_CATEGORY;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $postType
     * @param array $orderings
     * @param string|null $status
     * @return ?Criteria
     */
    public function createCriteria(
        int $offset,
        int $limit,
        string $postType,
        array $orderings,
        ?string $status
    ): ?Criteria {
        $category = $this->getPostCategory();
        if (!$category) {
            return null;
        }
        $result = Expression::criteria()
            ->where(
                Expression::eq('category_id', $category->getId())
            )->andWhere(
                Expression::eq('type', $postType)
            )
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy(
                $orderings
            );
        if ($status) {
            $result->andWhere(Expression::eq('status', $status));
        }
        return $result;
    }

    public function getPostCategory(): ?PostCategory
    {
        if ($this->categoryInit) {
            return $this->postCategory;
        }
        $this->categoryInit = true;
        return $this->postCategory = is_int($this->identity)
            ? $this->module->findCategoryById($this->identity)
            : $this->module->findCategoryBySlug($this->identity);
    }

    /**
     * {@inheritDoc}
     */
    public function getPosts(
        int $offset = 0,
        int $limit = 10,
        string $postType = Post::TYPE_POST,
        array $orderBy = [
            'id' => Criteria::DESC
        ],
        ?string $status = null
    ): ?LazyResultCriteria {
        if (!$this->getPostCategory()) {
            return null;
        }
        return parent::getPosts($offset, $limit, $postType, $orderBy, $status);
    }

    public function __get(string $name)
    {
        if (parent::__isset($name)) {
            return $this->get($name);
        }
        return $this->getPostCategory()?->$name;
    }

    public function __isset(string $name): bool
    {
        return parent::__isset($name) || isset($this->getPostCategory()?->$name);
    }

    public function __call(string $name, array $arguments)
    {
        return $this->getPostCategory()?->$name(...$arguments);
    }
}
