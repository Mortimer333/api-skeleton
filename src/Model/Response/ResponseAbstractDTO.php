<?php

namespace App\Model\Response;

use OpenApi\Attributes as SWG;

class ResponseAbstractDTO
{
    #[SWG\Property(example: 'Request message', description: 'Description of the request')]
    public string $message;

    #[SWG\Property(example: '200', description: 'HTTP code')]
    public int $status;

    #[SWG\Property(example: 'true', description: 'If request was successful')]
    public bool $success;

    /**
     * @var array<mixed> $data
     */
    #[SWG\Property(example: '{}', description: 'Additional data from server', type: 'object')]
    public array $data;

    #[SWG\Property(example: 'null', description: 'Current offset', nullable: true)]
    public ?int $offset;

    #[SWG\Property(example: 'null', description: 'Current limit', nullable: true)]
    public ?int $limit;

    #[SWG\Property(example: 'null', description: 'Total items', nullable: true)]
    public ?int $total;

    /**
     * @var array<string> $errors
     */
    #[SWG\Property(example: '[]', description: 'Additional errors', type: 'string[]')]
    public array $errors;
}
