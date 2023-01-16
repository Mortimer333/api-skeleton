<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\AccessControlService;
use App\Service\BackgroundProcessService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected AccessControlService $accessControlService,
        protected BackgroundProcessService $backgroundProcessService, // added to make unit tests work
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();
        $request = $event->getRequest();

        // when a controller class defines multiple action methods, the controller
        // is returned as [$controllerInstance, 'methodName']
        if (is_array($controller)) {
            $controller = $controller[0];
        }

        $this->accessControlService->validate($controller, $request);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
