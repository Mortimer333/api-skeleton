<?php

declare(strict_types=1);

namespace App\Constraint;

use Symfony\Component\Validator\Constraints as Assert;

class PaginationConstraint
{
    public static function get(): Assert\Collection
    {
        return new Assert\Collection([
            'limit' => new Assert\Optional([
                new Assert\Positive(['message' => 'Limit have to be a positive number']),
            ]),
            'offset' => new Assert\Optional([
                new Assert\PositiveOrZero(['message' => 'Offset have to be a positive number']),
            ]),
            'sort' => new Assert\Optional([
                new Assert\Count(['min' => 1]),
                new Assert\Type(['type' => 'array', 'message' => 'Sort have to be an array']),
                new Assert\All(['constraints' => [
                    new Assert\Collection([
                        'column' => [
                            new Assert\Type(['type' => 'string', 'message' => 'Column name must be a string']),
                            new Assert\Regex([
                                'pattern' => '/\s/',
                                'match' => false,
                                'message' => 'Column name cannot contain whitespaces',
                            ]),
                            new Assert\Regex([
                                'pattern' => '/[!@#$%^&*()+\-=\[\]{};\':"\\|,.<>\/?]+/',
                                'match' => false,
                                'message' => 'Column name cannot contain symbols except underscore',
                            ]),
                        ],
                        'direction' => [
                            new Assert\Choice([
                                'choices' => ['ASC', 'DESC'],
                                'message' => 'Available directions are: `ASC`, `DESC`.',
                            ]),
                        ],
                    ]),
                ]]),
            ]),
        ]);
    }
}
