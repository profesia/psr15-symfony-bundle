<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\RequestHandler\Factory;

use Profesia\Symfony\Psr15Bundle\RequestHandler\SymfonyControllerRequestHandler;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SymfonyControllerRequestHandlerFactory
{
    private HttpFoundationFactoryInterface $foundationHttpFactory;
    private HttpMessageFactoryInterface    $psrHttpFactory;
    private RequestStack                   $requestStack;

    public function __construct(
        HttpFoundationFactoryInterface $foundationHttpFactory,
        HttpMessageFactoryInterface $psrHttpFactory,
        RequestStack $requestStack
    ) {
        $this->foundationHttpFactory = $foundationHttpFactory;
        $this->psrHttpFactory        = $psrHttpFactory;
        $this->requestStack          = $requestStack;
    }

    public function create(callable $symfonyCallable, array $symfonyCallableArguments = []): SymfonyControllerRequestHandler
    {
        return new SymfonyControllerRequestHandler(
            $this->foundationHttpFactory,
            $this->psrHttpFactory,
            $this->requestStack,
            $symfonyCallable,
            $symfonyCallableArguments
        );
    }
}