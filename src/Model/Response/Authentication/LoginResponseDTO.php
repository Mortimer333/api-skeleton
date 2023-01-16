<?php

namespace App\Model\Response\Authentication;

use App\Model\Response\SuccessDTO;
use OpenApi\Attributes as SWG;

class LoginResponseDTO extends SuccessDTO
{
    #[SWG\Property(example: 'User authenticated', description: 'Description of the successful request')]
    public string $message;

    /**
     * @var array<string> $data
     */
    #[SWG\Property(
        example: '{'
            . '"token": "eyJhbGciOiJQUzI1NiIsImp0aSI6MSwiaXNzIjoiQm...bbZfGkcZHhXxlR2pLSRGeUpcovb0CdQb88nfWw",'
            . '"user": {"roles" : ["ROLE_USER"]} '
            . '}',
        description: 'Authentication token',
        type: 'object',
        properties: [
            new SWG\Property(property: 'token', type: 'string'),
            new SWG\Property(property: 'user', type: 'object', properties: [
                new SWG\Property(property: 'roles', type: 'string[]'),
            ]),
        ]
    )]
    public array $data;
}
