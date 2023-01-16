<?php

namespace App\Model\Response;

use OpenApi\Attributes as SWG;

class SuccessDTO extends ResponseAbstractDTO
{
    #[SWG\Property(example: 'Successful request', description: 'Description of the successful request')]
    public string $message;
}
