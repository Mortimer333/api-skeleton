<?php

namespace App\Model\Response;

use OpenApi\Attributes as SWG;

class FailureDTO extends ResponseAbstractDTO
{
    #[SWG\Property(example: 'Request failed', description: 'Description of the failed request')]
    public string $message;

    #[SWG\Property(example: '500', description: 'HTTP code')]
    public int $status;

    #[SWG\Property(example: 'false', description: 'If request was successful')]
    public bool $success;
}
