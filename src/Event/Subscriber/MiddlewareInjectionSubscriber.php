<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Event\Subscriber;

use Profesia\Symfony\Psr15Bundle\Adapter\SymfonyControllerAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Controller\ErrorController;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class MiddlewareInjectionSubscriber implements EventSubscriberInterface
{
    private SymfonyControllerAdapter $symfonyControllerAdapter;

    public function __construct(SymfonyControllerAdapter $symfonyControllerAdapter)
    {
        $this->symfonyControllerAdapter = $symfonyControllerAdapter;
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $controller = $event->getController();
        if ($controller instanceof ErrorController) {
            return;
        }

        if (self::isMainRequest($event) === false) {
            return;
        }

        $this->symfonyControllerAdapter->setOriginalResources($controller, $event->getRequest(), $event->getArguments());
        $event->setController(
            [
                $this->symfonyControllerAdapter,
                '__invoke'
            ]
        );
    }

    /**
     * @return array<string, array>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', -1]
        ];
    }

    private static function isMainRequest(KernelEvent $event): bool
    {
        return ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST);
    }
}
