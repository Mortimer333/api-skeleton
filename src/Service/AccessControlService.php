<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NotDoubleSubmitAuthenticatedController;
use App\Contract\NotTokenAuthenticatedController;
use App\Entity\User;
use App\Service\Util\BinUtilService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;

class AccessControlService
{
    public function __construct(
        protected Security $security,
        protected BinUtilService $baseUtilService,
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

    protected function validateRoutesAccess(string $path): void
    {
        if (preg_match('/^\/(_\/user)/', $path)) {
            /** @var ?User $user */
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
        $content = json_decode($request->getContent(), true);

        $res = ( // If Swagger request
                (
                    $this->baseUtilService->isDev()
                    && !$request->headers->get('X-Swagger')
                )
                || !$this->baseUtilService->isDev()
            )
            && !$controller instanceof NotDoubleSubmitAuthenticatedController
            && !in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])
            && (
                !$request->headers->get('x-csrf-token')
                || !isset($content['CSRF-Token'])
                || $request->headers->get('x-csrf-token') !== $content['CSRF-Token']
            );

        if ($res) {
            throw new AccessDeniedHttpException('CSRF attack');
        }
    }
}
