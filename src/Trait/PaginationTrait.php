<?php

declare(strict_types=1);

namespace App\Trait;

use App\Constraint\PaginationConstraint;
use App\Service\Util\HttpUtilService;
use Doctrine\ORM\QueryBuilder;

trait PaginationTrait
{
    /**
     * Controls:
     *  - limit
     *  - offset
     *  - sort - [{"column":"id","direction":"DESC"}].
     *
     * You can also pass callback which allows to add additional changes to the query builder
     *
     * @param array<int|array<string>> $pagination
     *
     * @return array<mixed>
     */
    public function list(array $pagination, callable $callback = null): array
    {
        $total = $this->count($pagination, $callback);
        HttpUtilService::setTotal($total);

        return $this->buildQuery($pagination, $callback)
            ->getQuery()
            ->getResult()
        ;
    }

    public function count(array $pagination, callable $callback = null): int
    {
        return $this->buildQuery($pagination, $callback, false)
            ->select('count(p.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param array<int|array<string>> $pagination
     */
    protected function buildQuery(array $pagination, callable $callback = null, bool $addLimits = true): QueryBuilder
    {
        $this->validationService->validate($pagination, PaginationConstraint::get());
        if (HttpUtilService::hasErrors()) {
            throw new \Exception('Pagination is invalid', 400);
        }
        $limit = $pagination['limit'] ?? false;
        $offset = $pagination['offset'] ?? false;
        /** @var array<string, mixed> $sort */
        $sort = $pagination['sort'] ?? [];

        $qb = $this->createQueryBuilder('p');

        if (false !== $limit && $addLimits) {
            $qb->setMaxResults((int) $limit);
            HttpUtilService::setLimit((int) $limit);
        }

        if (false !== $offset && $addLimits) {
            $qb->setFirstResult((int) $offset);
            HttpUtilService::setOffset((int) $offset);
        }

        foreach ($sort as $item) {
            $qb->addOrderBy('p.' . ($item['column'] ?? ''), $item['direction'] ?? '');
        }

        if ($callback) {
            $callback($qb);
        }

        return $qb;
    }
}
