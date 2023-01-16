<?php

namespace App\Model\Body;

use OpenApi\Attributes as SWG;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterDto
{
    #[SWG\Property(example: 'mail@mail.com', description: 'Unique e-mail')]
    #[Assert\NotBlank()]
    public string $username;

    #[Assert\NotBlank()]
    #[SWG\Property(
        example: 'password1@BIG',
        description: 'Password of length at least 12, one big letter,' .
            ' one small letter, one number and one special character'
    )]
    public string $password;

    #[Assert\NotBlank()]
    #[SWG\Property(
        example: 'password1@BIG',
        description: 'Repeated password'
    )]
    public string $passwordRepeat;

    #[Assert\NotBlank()]
    #[SWG\Property(example: 'John', description: 'User first name')]
    public string $firstname;

    #[Assert\NotBlank()]
    #[SWG\Property(example: 'Kowalski', description: 'User surname')]
    public string $surname;
}
