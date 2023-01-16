<?php

namespace App\Model\Response\Authentication;

use App\Model\Response\FailureDTO;
use OpenApi\Attributes as SWG;

class RegistrationFailedDTO extends FailureDTO
{
    #[SWG\Property(example: "Passwords don't match", description: 'Description of the failed request')]
    public string $message;

    #[SWG\Property(example: '400', description: 'HTTP code')]
    public int $status;

    /**
     * @var array<string> $errors
     */
    #[SWG\Property(
        example: '['
        . '"Passwords don\'t match",'
        . '"Password is too short, it is required to have 12 characters",'
        . '"Password is required to have uppercase characters",'
        . '"Password is required to have lowercase characters",'
        . '"Password is required to have numeric characters",'
        . '"Password is required to have special characters",'
        . '"Password is too weak",'
        . '"Username is not a valid e-mail",'
        . '"User with this e-mail already exist"'
        . ']',
        description: 'Possible additional errors',
        type: 'string[]'
    )]
    public array $errors;
}
