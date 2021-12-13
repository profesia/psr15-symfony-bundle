<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\RequestHandler\Factory;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\RequestHandler\Factory\SymfonyControllerRequestHandlerFactory;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class SymfonyControllerRequestHandlerFactoryTest extends MockeryTestCase
{
    public function testCanCreate()
    {
        /** @var MockInterface|HttpFoundationFactoryInterface $foundationHttpFactory */
        $foundationHttpFactory = Mockery::mock(HttpFoundationFactoryInterface::class);

        /** @var MockInterface|HttpMessageFactoryInterface $psrHttpFactory */
        $psrHttpFactory = Mockery::mock(HttpMessageFactoryInterface::class);

        /** @var MockInterface|RequestStack $requestStack */
        $requestStack = Mockery::mock(RequestStack::class);
        $factory      = new SymfonyControllerRequestHandlerFactory(
            $foundationHttpFactory,
            $psrHttpFactory,
            $requestStack
        );

        $callable   = function (TestObject $testObject) {
            return new Response(
                $testObject->getTest()
            );
        };
        $testObject = new TestObject('testing');
        $arguments  = [
            $testObject,
        ];
        $factory->create(
            $callable,
            $arguments
        );

        $this->assertTrue(true);
    }
}

class TestObject
{

    private string $test;

    public function __construct(string $test)
    {
        $this->test = $test;
    }

    public function getTest(): string
    {
        return $this->test;
    }
}
