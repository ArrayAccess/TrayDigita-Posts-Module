<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Archive;

use ArrayAccess\TrayDigita\App\Modules\Posts\Entities\PostCategory;
use ArrayAccess\TrayDigita\App\Modules\Posts\Posts;
use ArrayAccess\TrayDigita\Database\Helper\Expression;
use Doctrine\Common\Collections\Criteria;
use function strlen;
use function substr;

class TypeSearch extends TypeArchive
{
    public function __construct(
        Posts $module,
        public readonly string $searchQuery,
        bool $publicArea = true,
        ?PostCategory $postCategory = null,
        public readonly ?int $year = null,
        public readonly ?int $month = null,
        public readonly ?int $day = null
    ) {
        parent::__construct($module, $publicArea, $postCategory);
    }

    public function getType(): string
    {
        return self::TYPE_ARCHIVE;
    }

    public function isCategory(): bool
    {
        return $this->postCategory !== null;
    }

    public function createCriteria(
        int $offset,
        int $limit,
        string $postType,
        array $orderings,
        ?string $status
    ): ?Criteria {
        $result = parent::createCriteria(
            $offset,
            $limit,
            $postType,
            $orderings,
            $status
        )->andWhere(
            Expression::orX(
                Expression::eq('title', $this->searchQuery),
                Expression::startsWith('title', $this->searchQuery),
                Expression::endsWith('title', $this->searchQuery)
            )
        );
        $year = $this->getYear();
        $month = $this->getMonth();
        $day = $this->getDay();
        if ($year !== null) {
            $result->andWhere(
                Expression::eq('YEAR(published_at)', $year)
            );
        }
        if ($month !== null) {
            $result->andWhere(
                Expression::eq('MONTH(published_at)', $month)
            );
        }
        if ($day !== null) {
            $result->andWhere(
                Expression::eq('DAY(published_at)', $day)
            );
        }

        return $result;
    }

    public function getYear(): ?string
    {
        if (!$this->year || $this->year < 0) {
            return null;
        }

        $year = (string)$this->year;
        while (strlen($year) < 4) {
            $year = "0$year";
        }
        return substr($year, 0, 4);
    }

    public function getMonth(): ?string
    {
        if (!$this->month || $this->month < 1) {
            return null;
        }
        $month = $this->month % 12;
        $month = $month === 0 ? 12 : $month;
        $month = (string)$month;
        while (strlen($month) < 2) {
            $month = "0$month";
        }
        return $month;
    }

    public function getDay(): ?string
    {
        if (!$this->day || $this->day < 1) {
            return null;
        }
        $day = $this->day % 31;
        $day = $day === 0 ? 31 : $day;
        $day = (string)$day;
        while (strlen($day) < 2) {
            $day = "0$day";
        }
        return $day;
    }
}
