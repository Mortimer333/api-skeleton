<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Admin;
use App\Service\JWSService;
use App\Tests\Integration\BaseIntegrationAbstract;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class JWSServiceTest extends BaseIntegrationAbstract
{
    /**
     * @return array<mixed>
     */
    protected function prepare(
        bool $createToken = true,
        ?int $tokenUserId = null,
        string $userIdentifier = ''
    ): array {
        $jwsService = $this->getService(JWSService::class);
        $token = null;
        if ($createToken) {
            $user = $this->createMock(Admin::class);
            $user->method('getId')
                ->willReturn($tokenUserId);
            $user->method('getUserIdentifier')
                ->willReturn($userIdentifier);
            $token = 'Bearer ' . $jwsService->createToken($user);
        }

        return [
            $jwsService,
            $token,
        ];
    }

    public function testSuccessfulTokenValidation(): void
    {
        $userId = (int) floor(rand() * 100);
        [$jwsService, $token] = $this->prepare(
            tokenUserId: $userId,
        );

        $payload = $jwsService->validateAndGetPayload($token);
        $this->assertArrayHasKey('user_id', $payload);
    }

    public function testMissingTokenFailureTokenValidation(): void
    {
        [$jwsService, $token] = $this->prepare(createToken: false);

        $this->expectException(AccessDeniedHttpException::class);
        $jwsService->validateAndGetPayload($token);
    }

    public function testInvalidPayloadFailureTokenValidation(): void
    {
        $tokenUserId = (int) floor(rand() * 100);
        $entityUserId = (int) floor(rand() * 100);
        [$jwsService, $token] = $this->prepare(
            tokenUserId: null,
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageMatches("#Tokens' payload is invalid#");
        $jwsService->validateAndGetPayload($token);
    }
}
