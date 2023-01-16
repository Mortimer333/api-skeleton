<?php

declare(strict_types=1);

namespace App\Model\Parameter\Pagination;

use OpenApi\Attributes as SWG;

class SortItemDTO
{
    #[SWG\Property(example: 'id', description: 'Column to sort by')]
    public string $column;

    #[SWG\Property(example: 'DESC|ASC', description: 'Direction to sort by')]
    public string $direction;
}
