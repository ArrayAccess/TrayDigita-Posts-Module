<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Archive;

use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\PostCategory;
use ArrayAccess\TrayDigita\App\Modules\Posts\Posts;
use ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Abstracts\ArchiveBasedPostAbstract;
use ArrayAccess\TrayDigita\Database\Helper\Expression;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\Criteria;

class TypeArchive extends ArchiveBasedPostAbstract
{
    protected DateTimeInterface $date;

    public function __construct(
        Posts $module,
        bool $publicArea = true,
        ?PostCategory $postCategory = null,
        ?DateTimeInterface $dateTime = null
    ) {
        parent::__construct($module, $publicArea);
        $this->postCategory = $postCategory;
        $this->date = $dateTime ?? new DateTime();
    }

    public function getType(): string
    {
        return self::TYPE_ARCHIVE;
    }

    public function isCategory(): bool
    {
        return $this->postCategory !== null;
    }

    public function isFound(): bool
    {
        return $this->posts !== null;
    }

    public function createCriteria(
        int $offset,
        int $limit,
        string $postType,
        array $orderings,
        ?string $status
    ): ?Criteria {
        $result = Expression::criteria()
            ->where(Expression::eq('type', $postType))
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy($orderings);
        if ($status) {
            $result->andWhere(
                Expression::eq('status', $status)
            );
        }
        if ($this->postCategory) {
            $result->andWhere(
                Expression::eq(
                    'category_id',
                    $this->postCategory->getId()
                )
            );
        }
        return $result;
    }
}
