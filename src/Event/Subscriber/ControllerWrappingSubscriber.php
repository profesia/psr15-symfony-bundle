<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Event\Subscriber;

use Delvesoft\Psr15\RequestHandler\AbstractRequestHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerWrappingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => [
                ['onKernelController', -255]
            ]
        ];
    }
    
    public function onKernelController(ControllerEvent $event)
    {
        /*$callable = $event->getController();

        $controllerHandler = AbstractRequestHandler::createFromCallable($callable, function (...$args) {
            return new Response('Test from middleware');
        });

        $event->setController(
            [
                $controllerHandler,
                'handle'
            ]
        );*/
    }
}