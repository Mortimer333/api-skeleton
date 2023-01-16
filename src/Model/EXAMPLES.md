# Swagger examples

## Route

```php
#[Route('/{actionId<\d+>}/run/create', name: 'api_deploy_action_run_create', methods: 'POST')]
```

## Body:
```php
#[SWG\RequestBody(attachables: [new Model(type: RegisterDto::class)])]
```

## Response

```php
#[SWG\Response(
    description: 'Admin registrated',
    content: new Model(type: RegistrationResponseDTO::class),
    response: 200
)]
```

## Params


### Normal
```php
#[SWG\Parameter(
    name: 'actionId',
    in: 'path',
    required: true,
    description: 'Action ID',
)]
```

### Ref
```php
#[SWG\Parameter(
    name: 'pagination',
    in: 'query',
    content: new SWG\JsonContent(ref: new Model(type: PaginationDTO::class))
)]
```

## Class Definitions

```php
use App\Model\Definition\Deploy\DeployEntityDTO;
use OpenApi\Attributes as SWG;

class DeployUpdateDTO
{
    #[SWG\Property()]
    public DeployEntityDTO $deploy;
}
```
Goto `DeployEntityDTO`:
```php
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as SWG;

class DeployEntityDTO extends DeployDTO
{
    #[SWG\Property()]
    public int $id;

    /**
     * @var array<array<string|array<string|array<mixed>>>> $actions
     */
    #[SWG\Property(type: 'array', items: new SWG\Items(
        ref: new Model(type: DeployActionEntityDTO::class)
    ))]
    public array $actions;
}
```
Goto `DeployActionEntityDTO`:
```php
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as SWG;

class DeployActionEntityDTO extends DeployActionDTO
{
    #[SWG\Property()]
    public int $id;

    #[SWG\Property(type: 'array', items: new SWG\Items(
        ref: new Model(type: DeployActionArgumentEntityDTO::class)
    ))]
    public array $arguments;

    #[SWG\Property(type: 'array', items: new SWG\Items(
        ref: new Model(type: DeployActionDependEntityDTO::class)
    ))]
    public array $depends;
}
```
Goto `DeployActionDependEntityDTO`:
```php
use OpenApi\Attributes as SWG;

class DeployActionDependEntityDTO extends DeployActionDependDTO
{
    #[SWG\Property()]
    public int $id;
}
```
Goto `DeployActionDependDTO`:
```php
use OpenApi\Attributes as SWG;

class DeployActionDependDTO
{
    #[SWG\Property(example: 'action_name')]
    public string $name;
}
```


### Object:

```php
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
            . '"user": {"roles" : ["ROLE_ADMIN"]} '
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
```

### Object with ref:

```php
/**
 * @var array<array<string|array<string|array<mixed>>>> $data
 */
#[SWG\Property(type: 'object', properties: [
    new SWG\Property(property: 'run', ref: new Model(type: DeployActionRunItemEntityDTO::class)),
])]
public array $data;
```

### Array with examples:
```php
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
    . '"Admin with this e-mail already exist"'
    . ']',
    description: 'Possible additional errors',
    type: 'string[]'
)]
public array $errors;
```
