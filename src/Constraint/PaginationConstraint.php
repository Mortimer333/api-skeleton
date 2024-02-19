<?php

declare(strict_types=1);

namespace App\Constraint;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class PaginationConstraint
{
    /**
     * @return array<string>
     */
    protected static function getConnectors(): array
    {
        return ['(', ')', 'AND', 'OR'];
    }

    /**
     * @return array<string>
     */
    protected static function getOperators(): array
    {
        return ['!=', '=', 'IS', 'IS NOT', '<', '>', '<>', '>=', '<=', 'LIKE'];
    }

    protected static function getColumnRegex(): Assert\Regex
    {
        return new Assert\Regex([
            'pattern' => '/[!@#$%^&*()+\-=\[\]{};\':"\\|,<>\/?]+/', // allows only .
            'match' => false,
            'message' => 'Column name cannot contain symbols except underscore',
        ]);
    }

    public static function get(): Assert\Collection
    {
        $connectors = self::getConnectors();
        $operators = self::getOperators();
        $columnNameRegex = self::getColumnRegex();

        return new Assert\Collection([
            'join' => new Assert\Optional([
                new Assert\Count(['min' => 1, 'minMessage' => 'Join cannot be empty']),
                new Assert\Type(['type' => 'array', 'message' => 'Join has to be an array']),
                new Assert\All(['constraints' => [
                    new Assert\Type(['type' => 'array', 'message' => 'Join has to be an array']),
                    new Assert\Count([
                        'min' => 2,
                        'max' => 2,
                        'minMessage' => 'Join value must have two items',
                        'maxMessage' => 'Join value must have two items',
                    ]),
                    new Assert\All(['constraints' => [
                        new Assert\NotBlank(['message' => 'Missing join column or alias']),
                        new Assert\Type(['type' => 'string', 'message' => 'Join column name must be a string']),
                        new Assert\Regex([
                            'pattern' => '/\s/',
                            'match' => false,
                            'message' => 'Join column name cannot contain whitespaces',
                        ]),
                        $columnNameRegex,
                    ]]),
                ]]),
            ]),
            'group' => new Assert\Optional([
                new Assert\Count(['min' => 1, 'minMessage' => 'Group cannot be empty']),
                new Assert\Type(['type' => 'array', 'message' => 'Group has to be an array']),
                new Assert\All(['constraints' => [
                    new Assert\NotBlank(['message' => 'Missing group column or alias']),
                    new Assert\Type(['type' => 'string', 'message' => 'Group column name must be a string']),
                    new Assert\Regex([
                        'pattern' => '/\s/',
                        'match' => false,
                        'message' => 'Group column name cannot contain whitespaces',
                    ]),
                    $columnNameRegex,
                ]]),
            ]),
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
                            new Assert\NotBlank(['message' => 'Missing sort column']),
                            new Assert\Type(['type' => 'string', 'message' => 'Column name must be a string']),
                            new Assert\Regex([
                                'pattern' => '/\s/',
                                'match' => false,
                                'message' => 'Column name cannot contain whitespaces',
                            ]),
                            $columnNameRegex,
                        ],
                        'direction' => [
                            new Assert\NotBlank(['message' => 'Missing sort direction']),
                            new Assert\Choice([
                                'choices' => ['ASC', 'DESC'],
                                'message' => 'Available directions are: `ASC`, `DESC`.',
                            ]),
                        ],
                    ]),
                ]]),
            ]),
            'filter' => new Assert\Optional([
                new Assert\Count(['min' => 1]),
                new Assert\Type(['type' => 'array', 'message' => 'Filter has to be an array']),
                new Assert\All(['constraints' => [
                    new Assert\AtLeastOneOf(['constraints' => [
                        new Assert\Choice([
                            'choices' => $connectors,
                            'message' => 'No connector match.'
                                . ' Available connectors: "' . implode('", "', $connectors) . '".',
                        ]),
                        new Assert\Sequentially([
                            new Assert\Type(['type' => 'array', 'message' => 'All items have to be arrays']),
                            new Assert\Count(['min' => 3, 'max' => 3]),
                            new Assert\Collection([
                                [
                                    new Assert\NotBlank(['message' => 'Missing column name']),
                                    new Assert\Type([
                                        'type' => 'string',
                                        'message' => 'Filter between item value name must be a string',
                                    ]),
                                    $columnNameRegex,
                                ],
                                [
                                    new Assert\NotBlank(['message' => 'Missing operator']),
                                    new Assert\Choice([
                                        'choices' => $operators,
                                        'message' => 'No operator match.'
                                            . ' Available operators: "' . implode('", "', $operators) . '".',
                                    ]),
                                ],
                                [
                                    new Assert\Type([
                                        'type' => 'string',
                                        'message' => 'Value must be passed as a string or numeric',
                                    ]),
                                ],
                            ]),
                        ]),
                        // Allow array for IN and NOT IN - had to repeat validation because using nested
                        // Assert\AtLeastOneOf inside Collection results in unexpected behaviour and allow for
                        // passing any value anywhere
                        new Assert\Sequentially([
                            new Assert\Type(['type' => 'array', 'message' => 'All items have to be arrays']),
                            new Assert\Count(['min' => 3, 'max' => 3]),
                            new Assert\Collection([
                                [
                                    new Assert\NotBlank(['message' => 'Missing column name']),
                                    new Assert\Type([
                                        'type' => 'string',
                                        'message' => 'Filter between item value name must be a string',
                                    ]),
                                    $columnNameRegex,
                                ],
                                [
                                    new Assert\NotBlank(['message' => 'Missing operator']),
                                    new Assert\Choice([
                                        'choices' => ['IN', 'NOT IN'],
                                        'message' => 'No operator match.'
                                            . ' Available operators: "' . implode('", "', ['IN', 'NOT IN']) . '".',
                                    ]),
                                ],
                                [
                                    new Assert\Sequentially([
                                        new Assert\Type([
                                            'type' => 'array',
                                            'message' => 'Value must be passed as a array',
                                        ]),
                                        new Assert\Count(['min' => 1]),
                                        new Assert\AtLeastOneOf([
                                            new Assert\Type([
                                                'type' => 'string',
                                                'message' => 'Value for IN must be passed as a string or numeric',
                                            ]),
                                            new Assert\Type([
                                                'type' => 'number',
                                                'message' => 'Value for IN must be passed as a string or numeric',
                                            ]),
                                        ]),
                                    ]),
                                ],
                            ]),
                        ]),
                        // Allow numeric value - had to repeat validation because using nested Assert\AtLeastOneOf
                        // inside Collection results in unexpected behaviour and allow for passing any value anywhere
                        new Assert\Sequentially([
                            new Assert\Type(['type' => 'array', 'message' => 'All items have to be arrays']),
                            new Assert\Count(['min' => 3, 'max' => 3]),
                            new Assert\Collection([
                                [
                                    new Assert\NotBlank(['message' => 'Missing column name']),
                                    new Assert\Type([
                                        'type' => 'string',
                                        'message' => 'Filter between item value name must be a string',
                                    ]),
                                    $columnNameRegex,
                                ],
                                [
                                    new Assert\NotBlank(['message' => 'Missing operator']),
                                    new Assert\Choice([
                                        'choices' => $operators,
                                        'message' => 'No operator match.'
                                            . ' Available operators: "' . implode('", "', $operators) . '".',
                                    ]),
                                ],
                                [
                                    new Assert\Type([
                                        'type' => 'numeric',
                                        'message' => 'Value must be passed as a string or numeric',
                                    ]),
                                ],
                            ]),
                        ]),
                        // BETWEEN exception
                        new Assert\Sequentially([
                            new Assert\Type(['type' => 'array', 'message' => 'All between items have to be arrays']),
                            new Assert\Count(['min' => 5, 'max' => 5]),
                            new Assert\Collection([
                                [
                                    new Assert\NotBlank(['message' => 'Missing column name']),
                                    new Assert\Type([
                                        'type' => 'string',
                                        'message' => 'Column name must be a string',
                                    ]),
                                    $columnNameRegex,
                                ],
                                [
                                    new Assert\NotBlank(['message' => 'Missing operator']),
                                    new Assert\Choice([
                                        'choices' => ['BETWEEN'],
                                        'message' => 'No BETWEEN operator found at expected index.',
                                    ]),
                                ],
                                [
                                    new Assert\NotBlank(['message' => 'Between value cannot be empty']),
                                    new Assert\Type([
                                        'type' => 'string',
                                        'message' => 'Value must be passed as a string',
                                    ]),
                                ],
                                [
                                    new Assert\NotBlank(['message' => 'Missing between operator']),
                                    new Assert\Choice([
                                        'choices' => ['AND'],
                                        'message' => 'BETWEEN operator: AND, was not found at expected index.',
                                    ]),
                                ],
                                [
                                    new Assert\NotBlank(['message' => 'Between value cannot be empty']),
                                    new Assert\Type([
                                        'type' => 'string',
                                        'message' => 'Value must be passed as a string',
                                    ]),
                                ],
                            ]),
                        ]),
                    ]]),
                ]]),
            ]),
        ]);
    }
}
