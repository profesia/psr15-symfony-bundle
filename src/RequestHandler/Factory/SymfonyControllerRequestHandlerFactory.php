<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\RequestHandler\Factory;

use Delvesoft\Symfony\Psr15Bundle\RequestHandler\SymfonyControllerRequestHandler;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SymfonyControllerRequestHandlerFactory
{
    /** @var HttpFoundationFactoryInterface */
    private $foundationHttpFactory;

    /** @var HttpMessageFactoryInterface */
    private $psrHttpFactory;

    /** @var RequestStack */
    private $requestStack;

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