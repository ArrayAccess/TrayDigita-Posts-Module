<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Posts\PostTypes\Archive;

use ArrayAccess\TrayDigita\Database\Helper\Expression;
use Doctrine\Common\Collections\Criteria;

class TypeMonthlyArchive extends TypeArchive
{
    public function getType(): string
    {
        return self::TYPE_MONTH;
    }

    public function createCriteria(
        int $offset,
        int $limit,
        string $postType,
        array $orderings,
        ?string $status
    ): ?Criteria {
        return parent::createCriteria(
            $offset,
            $limit,
            $postType,
            $orderings,
            $status
        )->andWhere(
            Expression::eq(
                'YEAR(published_at)',
                $this->date->format('Y')
            )
        )->where(
            Expression::eq(
                'MONTH(published_at)',
                $this->date->format('m')
            )
        );
    }
}
