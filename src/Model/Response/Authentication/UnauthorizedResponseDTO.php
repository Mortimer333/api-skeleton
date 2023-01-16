<?php

namespace App\Model\Response\Authentication;

use App\Model\Response\FailureDTO;
use OpenApi\Attributes as SWG;

class UnauthorizedResponseDTO extends FailureDTO
{
    #[SWG\Property(example: 'Wrong password or username', description: 'Description of the failed request')]
    public string $message;

    #[SWG\Property(example: '401', description: 'HTTP code')]
    public int $status;
}
