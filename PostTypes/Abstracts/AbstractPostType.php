<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Abstracts;

use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\Post;
use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\PostCategory;
use ArrayAccess\TrayDigita\App\Modules\Posts\Posts;
use ArrayAccess\TrayDigita\App\Modules\Users\Users;
use ArrayAccess\TrayDigita\Auth\Roles\Interfaces\PermissionInterface;
use ArrayAccess\TrayDigita\Auth\Roles\Interfaces\RoleInterface;
use ArrayAccess\TrayDigita\Database\Helper\Expression;
use ArrayAccess\TrayDigita\Database\Result\LazyResultCriteria;
use ArrayAccess\TrayDigita\Util\Filter\Consolidation;
use Doctrine\Common\Collections\Criteria;
use function is_bool;
use function strtolower;

abstract class AbstractPostType
{
    public const TYPE_CATEGORY = 'category';

    public const TYPE_SINGULAR = 'singular';

    public const TYPE_SEARCH = 'search';

    public const TYPE_ARCHIVE = 'archive';

    public const TYPE_YEAR = 'year';

    public const TYPE_MONTH = 'month';

    public const TYPE_DAY = 'day';

    protected ?Post $post = null;

    protected ?LazyResultCriteria $posts = null;

    protected ?PostCategory $postCategory = null;

    public function __construct(
        public readonly Posts $module,
        public readonly bool $publicArea = true
    ) {
    }

    public function getLastPosts(): ?LazyResultCriteria
    {
        return $this->posts;
    }

    public function canViewProtectedPost(
        PermissionInterface $permission,
        ?RoleInterface $role = null
    ): bool {
        $result = $role && $permission->permitted($role, 'can_view_protected_posts');
        $newResult = $this->module->getManager()?->dispatch(
            'postType.canViewProtectedPost',
            $result,
            $permission,
            $role,
            $this
        );
        return is_bool($newResult) ? $newResult : $result;
    }

    public function permitted(): bool
    {
        $post = $this->getPost();
        if (!$post) {
            return false;
        }

        /**
         * @var Users $users
         */
        $users = $this->module->getModules()->get(Users::class);
        $isPublished = $post->isPublished();
        $permission = $users->getPermission();
        $admin = $users->getAdminAccount();
        $user = $users->getUserAccount();
        $canEditPost = $admin && (
                $permission->permitted($admin, 'can_edit_posts')
                || $permission->permitted($admin, 'can_preview_posts')
            );

        if ($canEditPost) {
            return true;
        }

        if (!$isPublished || $this->isRevision()) {
            return false;
        }

        if ($this->publicArea && $this->isPasswordProtected()) {
            return $this->canViewProtectedPost(
                $permission,
                $user
            );
        }

        return true;
    }

    public function get(string $name): mixed
    {
        return match ($name) {
            'posts' => $this->getPosts(),
            'post' => $this->getPost(),
            'postCategory',
            'category' => $this->getPostCategory(),
            'permitted' => $this->permitted(),
            'revisions' => $this->getRevisions(),
            'is_archive',
            'isArchive' => $this->isArchive(),
            'is_search',
            'isSearch' => $this->isSearch(),
            'is_category',
            'isCategory' => $this->isCategory(),
            'is_year',
            'isYear' => $this->isYear(),
            'is_month',
            'isMonth' => $this->isMonth(),
            'is_day',
            'isDay' => $this->isDay(),
            'is_singular',
            'isSingular' => $this->isSingular(),
            'is_revision',
            'isRevision' => $this->isRevision(),
            'post_type',
            'postType' => $this->getPostType(),
            'is_password_protected',
            'isPasswordProtected' => $this->isPasswordProtected(),
            'is_found',
            'isFound' => $this->isFound(),
            default => null
        };
    }

    public function getRevisions(
        int $offset = 0,
        int $limit = 10,
        bool $desc = true
    ): ?LazyResultCriteria {
        $post = $this->getPost();
        if ($post->isRevision()) {
            $post = $post->getParent();
        }
        if ($post->isRevision()) {
            return null;
        }
        return $post ? $this->module->getPostFinder()
            ->findByCriteria(
                Expression::criteria()
                    ->where(
                        Expression::eq('parent_id', $post->getId())
                    )->andWhere(
                        Expression::eq('type', Post::TYPE_REVISION)
                    )
                    ->setFirstResult($offset)
                    ->setMaxResults($limit)
                    ->orderBy(['id' => $desc ? Criteria::DESC : Criteria::ASC])
            ) : null;
    }

    public function isRevision(): bool
    {
        return $this->getPost()?->isRevision();
    }

    public function isArchive(): bool
    {
        return $this->typeIs(self::TYPE_ARCHIVE)
            && $this->getPosts() !== null
            || $this->isSearch()
            || $this->isYear()
            || $this->isMonth()
            || $this->isDay();
    }

    public function getPosts(): ?LazyResultCriteria
    {
        return $this->posts;
    }

    public function isSearch(): bool
    {
        return $this->typeIs(self::TYPE_SEARCH)
            && $this->getPosts() !== null;
    }

    public function isYear(): bool
    {
        return $this->typeIs(self::TYPE_YEAR)
            && $this->getPosts() !== null;
    }

    public function isMonth(): bool
    {
        return $this->typeIs(self::TYPE_MONTH)
            && $this->getPosts() !== null;
    }

    public function isDay(): bool
    {
        return $this->typeIs(self::TYPE_DAY)
            && $this->getPosts() !== null;
    }

    public function isCategory(): bool
    {
        return $this->typeIs(self::TYPE_CATEGORY)
            && $this->getPostCategory() !== null;
    }

    public function getPostCategory(): ?PostCategory
    {
        return $this->postCategory;
    }

    public function isSingular(): bool
    {
        return $this->typeIs(self::TYPE_SINGULAR)
            && $this->getPost() !== null;
    }

    public function typeIs(string $type): bool
    {
        return strtolower(trim($this->getType())) === strtolower(trim($type));
    }

    public function getPostType(): ?string
    {
        return $this->getPost()?->getType();
    }

    abstract public function getType(): string;

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function isPasswordProtected(): bool
    {
        return $this->getPost()?->isPasswordProtected()
            && $this->getPost()->getPassword();
    }

    abstract public function isFound(): bool;

    public function getParentPost(): ?Post
    {
        return $this->getPost()?->getParent();
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function __isset(string $name): bool
    {
        return match ($name) {
            'posts',
            'post',
            'postCategory',
            'category',
            'permitted',
            'revisions',
            'is_archive',
            'isArchive',
            'is_search',
            'isSearch',
            'is_category',
            'isCategory',
            'is_year',
            'isYear',
            'is_month',
            'isMonth',
            'is_day',
            'isDay',
            'is_singular',
            'isSingular',
            'is_revision',
            'isRevision',
            'post_type',
            'postType',
            'is_password_protected',
            'isPasswordProtected',
            'is_found',
            'isFound' => true,
            default => false
        };
    }

    public function __debugInfo(): ?array
    {
        return Consolidation::debugInfo(
            $this,
            excludeKeys: ['post', 'posts', 'postCategory']
        );
    }
}
