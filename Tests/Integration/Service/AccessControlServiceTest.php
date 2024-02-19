<?php

namespace App\Tests\Integration\Service;

use App\Entity\User;
use App\Service\AccessControlService;
use App\Service\Util\BinUtilService;
use App\Tests\Integration\BaseIntegrationAbstract;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AccessControlServiceTest extends BaseIntegrationAbstract
{
    public function testSuccessfulValidation(): void
    {
        $bodyToken = $headerToken = $token = 'test';
        $accessControlService = $this->getAccessControlService(new User());
        $request = Request::create('http://url.com/_/admin/super', cookies: ['CSRF-Token' => $bodyToken]);
        $request->setMethod('POST');
        $mapHeaders = [
            'x-csrf-token' => $headerToken,
            'authorization' => $token,
        ];
        $request->headers = new HeaderBag($mapHeaders);

        $controller = new \stdClass();

        $accessControlService->validate($controller, $request);
    }

    public function testCsrfAttackProperlyRecognized(): void
    {
        $bodyToken = 'test';
        $headerToken = 'testnot';
        $accessControlService = $this->getAccessControlService(new User());
        $request = Request::create(
            'http://url.com/_/user/super',
            cookies: ['CSRF-Token' => $bodyToken],
        );
        $request->setMethod('POST');
        $mapHeaders = [
            'x-csrf-token' => $headerToken,
            'authorization' => 'test',
        ];
        $request->headers = new HeaderBag($mapHeaders);

        $controller = new \stdClass();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('CSRF attack');
        $accessControlService->validate($controller, $request);
    }

    public function testMissingToken(): void
    {
        $bodyToken = $headerToken = 'test';
        $accessControlService = $this->getAccessControlService(new User());
        $request = Request::create(
            'http://url.com/_/user/super',
            cookies: ['CSRF-Token' => $bodyToken],
        );
        $request->setMethod('POST');
        $mapHeaders = [
            'x-csrf-token' => $headerToken,
        ];
        $request->headers = new HeaderBag($mapHeaders);

        $controller = new \stdClass();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Token is required to access this resource');
        $accessControlService->validate($controller, $request);
    }

    public function testPermissionToRouteNotGrantedWhenMissingUser(): void
    {
        $bodyToken = $headerToken = $token = 'test';
        $accessControlService = $this->getAccessControlService();
        $request = Request::create(
            'http://url.com/_/user/super',
            cookies: ['CSRF-Token' => $bodyToken],
        );
        $request->setMethod('POST');
        $mapHeaders = [
            'x-csrf-token' => $headerToken,
            'authorization' => $token,
        ];
        $request->headers = new HeaderBag($mapHeaders);

        $controller = new \stdClass();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You need to be logged in to access this resource');
        $accessControlService->validate($controller, $request);
    }

    protected function getAccessControlService(?object $user = null): AccessControlService
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')
            ->willReturn($user);

        $security->method('isGranted')
            ->willReturn(true);

        $requestStack = $this->getService(RequestStack::class);

        return new AccessControlService($security, new BinUtilService(), $requestStack);
    }
}
