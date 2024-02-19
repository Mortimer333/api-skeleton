<?php

declare(strict_types=1);

namespace App\Trait;

use App\Constraint\PaginationConstraint;
use App\Service\Util\HttpUtilService;
use App\Service\ValidationService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

trait PaginationTrait
{
    public function __construct(
        protected ValidationService $validationService,
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, self::ENTITY_CLASS);
    }

    /**
     * Controls:
     *  - limit
     *  - offset
     *  - sort - [{"column":"id","direction":"DESC"}]
     *  - filter - [["column", "=", "value"], "AND", ["column2", "!=", "value2"]].
     *  - join - [['table', 'alias']]
     *           (third it conditionType, fourth is condition, both nullable).
     *  - group - ['column', 'column2'].
     *
     * You can also pass callback which allows to add additional changes to the query builder
     *
     * @param array<string, int|array<int|string, string|array<string|null>>|array<array<string>>|null> $pagination
     *
     * @return array<mixed>
     */
    public function list(array $pagination, ?callable $callback = null): array
    {
        $total = $this->count($pagination, $callback);
        HttpUtilService::setTotal($total);

        $rows = $this->buildQuery($pagination, $callback)
            ->getQuery()
            ->getResult()
        ;

        return $rows;
    }

    public function count(array $pagination, ?callable $callback = null): int
    {
        /** @var array<string> $group */
        $group = $pagination['group'] ?? [];
        $select = 'count(p.id) as count';
        if (!empty($group)) {
            if (1 == count($group)) {
                $select = 'COUNT(DISTINCT ' . $this->decideAlias($group[0]) . ') as count';
            } else {
                $columns = '';
                foreach ($group as $column) {
                    $column = $this->decideAlias($column);

                    $columns .= $column . ',';
                }
                $columns = trim($columns, ',');
                $select = 'COUNT(DISTINCT CONCAT(' . $columns . ')) as count';
            }
        }

        $rows = $this->buildQuery($pagination, $callback, false, false)
            ->select($select)
            ->getQuery()
        ;

        $rows = $rows->getResult()
        ;

        $total = 0;
        foreach ($rows as $row) {
            $total += $row['count'];
        }

        return $total;
    }

    /**
     * @param array<string, int|array<int|string, string|array<string|null>>|array<array<string>>|null> $pagination
     */
    protected function buildQuery(
        array $pagination,
        ?callable $callback = null,
        bool $addLimits = true,
        bool $addGroupBy = true
    ): QueryBuilder {
        $this->validationService->validate($pagination, PaginationConstraint::get());
        if (HttpUtilService::hasErrors()) {
            throw new \Exception('Pagination is invalid', 400);
        }

        /** @var int|bool $limit */
        $limit = $pagination['limit'] ?? false;
        /** @var int|bool $offset */
        $offset = $pagination['offset'] ?? false;

        /** @var array<string, mixed> $sort */
        $sort = $pagination['sort'] ?? [];

        /** @var array<string|array<string|array<string|int>>> $filter */
        $filter = $pagination['filter'] ?? [];

        /** @var array<array<string>> $join */
        $join = $pagination['join'] ?? [];

        /** @var array<string> $group */
        $group = $pagination['group'] ?? [];

        $qb = $this->createQueryBuilder('p');

        $this->setLimit($qb, $limit, $addLimits);
        $this->setOffset($qb, $offset, $addLimits);
        $this->setJoins($qb, $join);
        $this->addOrderBy($qb, $sort);
        $this->addGroupBy($qb, $group, $addGroupBy);

        $where = $this->convertFilterToWhere($qb, $filter);
        if (strlen($where) > 0) {
            $qb->where($where); // @phpstan-ignore-line
        }

        if ($callback) {
            $callback($qb);
        }

        return $qb;
    }

    protected function setLimit(QueryBuilder $qb, int|bool $limit, bool $addLimits): void
    {
        if (false !== $limit && $addLimits) {
            $qb->setMaxResults((int) $limit);
            HttpUtilService::setLimit((int) $limit);
            HttpUtilService::setOffset(0);
        }
    }

    protected function setOffset(QueryBuilder $qb, int|bool $offset, bool $addLimits): void
    {
        if (false !== $offset && $addLimits) {
            $qb->setFirstResult((int) $offset);
            HttpUtilService::setOffset((int) $offset);
        }
    }

    /**
     * @param array<string, mixed> $sort
     */
    protected function addOrderBy(QueryBuilder $qb, array $sort): void
    {
        foreach ($sort as $item) {
            $column = $item['column'] ?? '';
            $column = $this->decideAlias($column);

            $qb->addOrderBy($column, $item['direction'] ?? '');
        }
    }

    /**
     * @param array<string> $group
     */
    protected function addGroupBy(QueryBuilder $qb, array $group, bool $addGroupBy): void
    {
        if (!$addGroupBy) {
            return;
        }

        foreach ($group as $column) {
            $column = $this->decideAlias($column);

            $qb->addGroupBy($column);
        }
    }

    /**
     * @param array<array<string>> $joins
     */
    protected function setJoins(QueryBuilder $qb, array $joins): void
    {
        foreach ($joins as $table) {
            $name = $this->decideAlias($table[0]);

            $qb->innerJoin($name, $table[1]); // @phpstan-ignore-line
        }
    }

    protected function decideAlias(string $name): string
    {
        if (false === strpos($name, '.')) {
            $name = 'p.' . $name;
        }

        return $name;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param array<string|array<string|array<string|int>|null>> $filter
     */
    protected function convertFilterToWhere(QueryBuilder $qb, array $filter): string
    {
        $counter = 0;
        $where = [];
        foreach ($filter as $item) {
            if (!is_array($item)) {
                $where[] = trim($item);
                continue;
            }

            if (\sizeof($item) < 3) {
                /** @var array<string> $item */
                $item = $item;
                $where[] = trim(implode(' ', $item));
                continue;
            }

            /** @var string $column */
            $column = $item[0];
            /** @var string $operator */
            $operator = $item[1];
            /** @var string|array<string|int>|null $value */
            $value = $item[2];

            $isIn = 'IN' === $operator || 'NOT IN' === $operator;
            if ($isIn && !is_array($value)) {
                throw new \Exception('Value for IN must be an array', 400);
            }

            $item[0] = $this->decideAlias($column);

            if (is_null($value)) {
                $item[2] = 'NULL';
            } else {
                $qb->setParameter('p' . $counter, $value);
                if ($isIn) {
                    $item[2] = '(:p' . $counter . ')';
                } else {
                    $item[2] = ':p' . $counter;
                }
            }
            ++$counter;

            // BETWEEN exception
            if (\sizeof($item) >= 5) {
                $qb->setParameter('p' . $counter, $item[4]);
                $item[4] = ':p' . $counter;
                ++$counter;
            }

            /** @var array<string> $item */
            $item = $item;

            $imploded = implode(' ', $item);

            $where[] = $imploded;
        }

        return implode(' ', $where);
    }
}
