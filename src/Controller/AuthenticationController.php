<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Model\Body\LoginDto;
use App\Model\Body\RegisterDto;
use App\Model\Response\Authentication\LoginResponseDTO;
use App\Model\Response\Authentication\RegistrationFailedDTO;
use App\Model\Response\Authentication\RegistrationResponseDTO;
use App\Model\Response\Authentication\UnauthorizedResponseDTO;
use App\Service\JWSService;
use App\Service\Util\BinUtilService;
use App\Service\Util\HttpUtilService;
use App\Service\ValidationService;
use Doctrine\Persistence\ManagerRegistry;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[SWG\Tag('Authentication')]
class AuthenticationController extends AbstractController
{
    public function __construct(
        protected HttpUtilService $httpUtilService,
        protected BinUtilService $baseUtilService,
    ) {
    }

    #[Route('/register', name: 'api_user_register', methods: 'POST')]
    #[SWG\RequestBody(attachables: [new Model(type: RegisterDto::class)])]
    #[SWG\Response(
        description: 'Registration attempt failed',
        content: new Model(type: RegistrationFailedDTO::class),
        response: 400
    )]
    #[SWG\Response(
        description: 'Admin registered',
        content: new Model(type: RegistrationResponseDTO::class),
        response: 200
    )]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        ManagerRegistry $doctrine,
        ValidationService $validationService,
    ): JsonResponse {
        $entity = $doctrine->getManager();

        $parameters = $this->httpUtilService->getBody($request);

        if ($parameters['password'] !== $parameters['passwordRepeat']) {
            $this->httpUtilService->addError("Passwords don't match");
        }

        if (!$validationService->validatePasswordStrength($parameters['password'], length: 12)) {
            $this->httpUtilService->addError('Password is too weak');
        }

        if (!filter_var($parameters['username'], FILTER_VALIDATE_EMAIL)) {
            $this->httpUtilService->addError('Username is not a valid e-mail');
        }

        $isDuplicate = (bool) $entity->getRepository(User::class)
            ->findOneBy(['email' => $parameters['username']]);

        if ($isDuplicate) {
            $this->httpUtilService->addError('User with this e-mail already exist');
        }

        if ($this->httpUtilService->hasErrors()) {
            throw new \InvalidArgumentException('Registration attempt failed', 400);
        }

        $user = new User();
        $plainPassword = $parameters['password'];

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plainPassword
        );

        $user->setEmail($parameters['username'])
            ->setPassword($hashedPassword)
            ->setFirstname($parameters['firstname'])
            ->setSurname($parameters['surname'])
        ;

        $entity->persist($user);
        $entity->flush();

        return $this->httpUtilService->jsonResponse('User registered');
    }

    #[Route('/login', name: 'api_user_login', methods: 'POST')]
    #[SWG\RequestBody(attachables: [new Model(type: LoginDto::class)])]
    #[SWG\Response(
        description: 'Returns the JWS authentication token',
        content: new Model(type: LoginResponseDTO::class),
        response: 200
    )]
    #[SWG\Response(
        description: 'User was unable to log in',
        content: new Model(type: UnauthorizedResponseDTO::class),
        response: 401
    )]
    public function login(
        JWSService $jwsService,
        Security $security
    ): JsonResponse {
        /** @var ?User $user */
        $user = $security->getUser();

        if (null === $user) {
            return $this->httpUtilService->jsonResponse(
                'Wrong password or username',
                JsonResponse::HTTP_UNAUTHORIZED,
                false,
            );
        }

        $token = $jwsService->createToken($user);

        return $this->httpUtilService->jsonResponse(
            'User authenticated',
            data: [
                'token' => $token,
                'user' => [
                    'roles' => $user->getRoles(),
                ],
            ],
        );
    }
}
