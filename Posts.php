<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts;

use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\Post;
use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\PostCategory;
use ArrayAccess\TrayDigita\App\Modules\Posts\Finder\CategoryFinder;
use ArrayAccess\TrayDigita\App\Modules\Posts\Finder\PostFinder;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Site;
use ArrayAccess\TrayDigita\Database\Result\LazyResultCriteria;
use ArrayAccess\TrayDigita\Module\AbstractModule;
use ArrayAccess\TrayDigita\Traits\Database\ConnectionTrait;
use ArrayAccess\TrayDigita\Traits\Service\TranslatorTrait;
use ArrayAccess\TrayDigita\Util\Filter\Consolidation;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\SchemaException;

final class Posts extends AbstractModule
{
    use TranslatorTrait,
        ConnectionTrait;

    protected ?PostFinder $postFinder = null;

    protected ?CategoryFinder $categoryFinder = null;

    protected string $name = 'Post & Articles';

    private bool $didInit = false;

    public function getName(): string
    {
        return $this->translateContext(
            'Post & Articles',
            'post-module',
            'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Module to make application support posts publishing',
            'post-module',
            'module'
        );
    }

    protected function doInit(): void
    {
        /** @noinspection DuplicatedCode */
        if ($this->didInit) {
            return;
        }

        Consolidation::registerAutoloader(__NAMESPACE__, __DIR__);
        $this->didInit = true;
        $kernel = $this->getKernel();
        $kernel->registerControllerDirectory(__DIR__ .'/Controllers');
        $this->getTranslator()?->registerDirectory('module', __DIR__ . '/Languages');
        $this->getConnection()->registerEntityDirectory(__DIR__.'/Entities');
    }

    public function getPostFinder(): ?PostFinder
    {
        return $this->postFinder ??= new PostFinder(
            $this->getConnection()
        );
    }

    public function getCategoryFinder(): ?CategoryFinder
    {
        return $this->categoryFinder ??= new CategoryFinder(
            $this->getConnection()
        );
    }

    public function findPostById(int $id): ?Post
    {
        return $this->getPostFinder()->find($id);
    }

    public function findCategoryById(int $id): ?PostCategory
    {
        return $this->getCategoryFinder()->find($id);
    }

    public function findPostBySlug(string $slug, int|Site|null $site = null): ?Post
    {
        return $this->getPostFinder()->findBySlug($slug, $site);
    }

    public function findCategoryBySlug(string $slug, int|Site|null $site = null): ?PostCategory
    {
        return $this->getCategoryFinder()->findBySlug($slug, $site);
    }

    /**
     * @throws SchemaException
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function searchPost(
        string $searchQuery,
        int|Site|null $site = null,
        int $limit = 10,
        int $offset = 0,
        array $orderBy = [],
        CompositeExpression|Comparison ...$expressions
    ): LazyResultCriteria {
        return $this->getPostFinder()->search(
            $searchQuery,
            $site,
            $limit,
            $offset,
            $orderBy,
            ...$expressions
        );
    }

    /**
     * @throws SchemaException
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function searchCategory(
        string $searchQuery,
        int|Site|null $site = null,
        int $limit = 10,
        int $offset = 0,
        array $orderBy = [],
        CompositeExpression|Comparison ...$expressions
    ): LazyResultCriteria {
        return $this->getCategoryFinder()->search(
            $searchQuery,
            $site,
            $limit,
            $offset,
            $orderBy,
            ...$expressions
        );
    }
}
