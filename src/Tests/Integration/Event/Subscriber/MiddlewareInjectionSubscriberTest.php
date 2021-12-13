<?php

declare(strict_types=1);


namespace Profesia\Symfony\Psr15Bundle\Tests\Integration\Event\Subscriber;


use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Adapter\SymfonyControllerAdapter;
use Profesia\Symfony\Psr15Bundle\Event\Subscriber\MiddlewareInjectionSubscriber;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ErrorController;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

class MiddlewareInjectionSubscriberTest extends MockeryTestCase
{
    public function testCanExitOnErrorController()
    {
        /** @var MockInterface|SymfonyControllerAdapter $adapter */
        $adapter = Mockery::mock(SymfonyControllerAdapter::class);
        $adapter->shouldNotReceive('setOriginalResources');

        $subscriber = new MiddlewareInjectionSubscriber(
            $adapter
        );

        /** @var MockInterface|HttpKernelInterface $httpKernel */
        $httpKernel = Mockery::mock(HttpKernelInterface::class);
        $controllerCallable = function () {
            return new Response();
        };

        $event = new ControllerArgumentsEvent(
            Mockery::mock(KernelInterface::class),
            new ErrorController(
                $httpKernel,
                $controllerCallable,
                Mockery::mock(ErrorRendererInterface::class)
            ),
            [],
            Mockery::mock(Request::class),
            HttpKernelInterface::SUB_REQUEST
        );


        $subscriber->onKernelControllerArguments($event);
    }

    public function testCanExitOnNotMainRequest()
    {
        /** @var MockInterface|SymfonyControllerAdapter $adapter */
        $adapter = Mockery::mock(SymfonyControllerAdapter::class);
        $adapter->shouldNotReceive('setOriginalResources');

        $subscriber = new MiddlewareInjectionSubscriber(
            $adapter
        );

        $controllerCallable = function () {
            return new Response();
        };

        $event = new ControllerArgumentsEvent(
            Mockery::mock(KernelInterface::class),
            $controllerCallable,
            [],
            Mockery::mock(Request::class),
            HttpKernelInterface::SUB_REQUEST
        );


        $subscriber->onKernelControllerArguments($event);
    }

    public function testCanOverrideControllerOnMasterRequest()
    {
        $controllerCallable = function () {
            return new Response();
        };

        $arguments = ['a', 'b', 'c'];

        /** @var MockInterface|Request $request */
        $request = Mockery::mock(Request::class);

        /** @var MockInterface|SymfonyControllerAdapter $adapter */
        $adapter = Mockery::mock(SymfonyControllerAdapter::class);
        $adapter
            ->shouldReceive('setOriginalResources')
            ->once()
            ->withArgs(
                [
                    $controllerCallable,
                    $request,
                    $arguments
                ]
            )->andReturnSelf();

        $subscriber = new MiddlewareInjectionSubscriber(
            $adapter
        );

        $event = new ControllerArgumentsEvent(
            Mockery::mock(KernelInterface::class),
            $controllerCallable,
            $arguments,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );


        $subscriber->onKernelControllerArguments($event);
    }

    public function testSubscriberConfig()
    {
        $config = MiddlewareInjectionSubscriber::getSubscribedEvents();

        $this->assertTrue(sizeof($config) === 1);
        $this->assertArrayHasKey(KernelEvents::CONTROLLER_ARGUMENTS, $config);
        $this->assertEquals(['onKernelControllerArguments', -1], $config[KernelEvents::CONTROLLER_ARGUMENTS]);
    }
}
