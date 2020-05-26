<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Event\Subscriber;

use Delvesoft\Symfony\Psr15Bundle\Adapter\SymfonyControllerAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Controller\ErrorController;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MiddlewareInjectionSubscriber implements EventSubscriberInterface
{
    /** @var SymfonyControllerAdapter */
    private $symfonyControllerAdapter;

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

        $this->symfonyControllerAdapter->setOriginalController($controller);
        $event->setController(
            [
                $this->symfonyControllerAdapter,
                '__invoke'
            ]
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', -1]
        ];
    }
}