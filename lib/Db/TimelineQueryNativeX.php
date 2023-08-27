<?php

declare(strict_types=1);

namespace OCA\Memories\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;

trait TimelineQueryNativeX
{
    public function transformNativeQuery(IQueryBuilder &$query, bool $aggregate)
    {
        if (!$aggregate) {
            $query->addSelect('m.epoch', 'f.size');
        }
    }
}
