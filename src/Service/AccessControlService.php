<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NotDoubleSubmitAuthenticatedController;
use App\Contract\NotTokenAuthenticatedController;
use App\Service\Util\BinUtilService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AccessControlService
{
    public function __construct(
        protected Security $security,
        protected BinUtilService $baseUtilService,
        protected RequestStack $requestStack,
    ) {
    }

    public function validate(object $controller, Request $request): void
    {
        if (!preg_match('/^\/(_\/(user|login))/', $request->getPathInfo())) {
            return;
        }

        $this->validateCSRFAttack($controller, $request);
        $this->validateTokenExists($controller, $request);
        $this->validateRoutesAccess($request->getPathInfo());
    }

    public function isSwaggerRequest(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        return $this->baseUtilService->isDev() && $request && $request->headers->get('X-Swagger');
    }

    protected function validateRoutesAccess(string $path): void
    {
        if (preg_match('/^\/(_\/user)/', $path)) {
            /** @var ?\App\Entity\User $user */
            $user = $this->security->getUser();
            if (null === $user) {
                throw new AccessDeniedHttpException('You need to be logged in to access this resource');
            }

            if (!$this->security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedHttpException('You need to be admin in to access this resource');
            }

            if (preg_match('/^\/(_\/user\/super)/', $path) && !$this->security->isGranted('ROLE_SUPER_ADMIN')) {
                throw new AccessDeniedHttpException('You need to be super admin in to access this resource');
            }
        }
    }

    protected function validateTokenExists(object $controller, Request $request): void
    {
        // Making sure that user will be verified by custom authorization
        if (
            !$controller instanceof NotTokenAuthenticatedController
            && !$request->headers->get('authorization')
        ) {
            throw new AccessDeniedHttpException('Token is required to access this resource');
        }
    }

    protected function validateCSRFAttack(object $controller, Request $request): void
    {
        $cookieToken = $request->cookies->get('CSRF-Token');

        $res = (!$this->isSwaggerRequest() || !$this->baseUtilService->isDev())       // is not swagger request on dev
            && !$controller instanceof NotDoubleSubmitAuthenticatedController         // is double submit check control
            && !in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])           // is change data request
            && (                                                                      // doesn't have csrf token or
                !$request->headers->get('x-csrf-token')                               //    tokens don't match
                || !isset($cookieToken)
                || $request->headers->get('x-csrf-token') !== $cookieToken
            );

        if ($res) {
            throw new AccessDeniedHttpException('CSRF attack');
        }
    }
}
