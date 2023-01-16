<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User;
use App\Model\Response\Token\RefreshTokenResponseDTO;
use App\Service\JWSService;
use App\Service\Util\HttpUtilService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[SWG\Tag('Token')]
#[Route('/token')]
class TokenController extends AbstractController
{
    public function __construct(
        protected HttpUtilService $httpUtilService,
    ) {
    }

    #[Route('/refresh', name: 'api_token_refresh', methods: 'PUT')]
    #[SWG\Response(
        description: 'Returns refreshed JWS authentication token',
        content: new Model(type: RefreshTokenResponseDTO::class),
        response: 200
    )]
    public function refresh(JWSService $jwsService, Security $security): JsonResponse
    {
        /** @var User $user */
        $user = $security->getUser();

        $token = $jwsService->createToken($user);

        return $this->httpUtilService->jsonResponse(
            'Token refreshed',
            data: [
                'token' => $token,
            ],
        );
    }
}
