<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Abstracts;

use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\Post;
use ArrayAccess\TrayDigita\Database\Result\LazyResultCriteria;
use Doctrine\Common\Collections\Criteria;
use function in_array;
use function is_string;
use function max;
use function strtolower;
use function strtoupper;
use function trim;

abstract class ArchiveBasedPostAbstract extends AbstractPostType
{
    /**
     * @var ?array for cached criteria
     */
    private ?array $cachedCriteria = null;
    private array $cachedCount = [];

    public function isFound(): bool
    {
        return $this->posts !== null;
    }

    public function getTotalPosts(
        string $postType = Post::TYPE_POST,
        ?string $status = null
    ): int {
        $postType = Post::normalizeType($postType);
        $key = $postType;
        if ($status) {
            $key .= "|$status";
        }
        if (isset($this->cachedCount[$key])) {
            return $this->cachedCount[$key];
        }
        $criteria = ['a.type = :type'];
        $params = ['type' => $postType];
        if ($status) {
            $params['status'] = $status;
            $criteria[] = 'a.status = :status';
        }

        $res = $this
            ->module
            ->getPostFinder()
            ->getConnection()
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('count(a.id) as count')
            ->from(
                $this
                    ->module
                    ->getPostFinder()
                    ->getRepository()
                    ->getClassName(),
                'a'
            )
            ->where(...$criteria)
            ->setMaxResults(1)
            ->getQuery()
            ->execute($params)[0] ?? [];
        return $this->cachedCount[$key] = (int)($res['count'] ?? 0);
    }

    /**
     * Get posts by criteria
     *
     * @param int $offset
     * @param int $limit
     * @param string $postType
     * @param array $orderBy
     * @param string|null $status
     * @return ?LazyResultCriteria
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
        $criteria = $this->filterCriteria(
            $offset,
            $limit,
            $postType,
            $orderBy,
            $status
        );
        if ($this->cachedCriteria === $criteria) {
            return $this->posts;
        }

        $this->cachedCriteria = $criteria;
        $criteria = $this->createCriteria(
            $criteria['offset'],
            $criteria['limit'],
            $criteria['postType'],
            $criteria['orderBy'],
            $criteria['status']
        );
        $this->posts = $criteria ? $this
            ->module
            ->getPostFinder()
            ->findByCriteria(
                $criteria
            ) : null;
        return $this->posts;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $postType
     * @param array $orderBy
     * @param string|null $status
     * @return array{offset: int, limit:int, postType: string, orderBy:array, status: ?string}
     */
    protected function filterCriteria(
        int $offset = 0,
        int $limit = 10,
        string $postType = Post::TYPE_POST,
        array $orderBy = [
            'id' => Criteria::DESC
        ],
        ?string $status = null
    ): array {
        $allowedOrder = [
            'created_at',
            'deleted_at',
            'published_at',
            'id',
            'title',
        ];
        $orderings = [];
        foreach ($orderBy as $key => $order) {
            if (is_string($key) && is_string($order)) {
                $key = $order;
                $order = 'ASC';
            }
            if (!is_string($key) || !is_string($order)) {
                continue;
            }
            $key = strtolower($key);
            if (!in_array($key, $allowedOrder)) {
                continue;
            }
            $order = strtoupper(trim($order));
            $orderings[$key] = $order === 'ASC' ? $order : 'DESC';
        }
        $orderings = empty($orderings) ? ['id' => Criteria::DESC] : $orderings;
        $offset = max($offset, 0);
        $limit = max($limit, 1);
        return [
            'offset' => $offset,
            'limit' => $limit,
            'postType' => Post::normalizeType($postType),
            'orderBy' => $orderings,
            'status' => $status
        ];
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $postType
     * @param array $orderings
     * @param string|null $status
     * @return ?Criteria
     */
    abstract public function createCriteria(
        int $offset,
        int $limit,
        string $postType,
        array $orderings,
        ?string $status
    ): ?Criteria;
}
