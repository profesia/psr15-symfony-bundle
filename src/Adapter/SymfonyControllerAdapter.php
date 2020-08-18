<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Adapter;

use Delvesoft\Symfony\Psr15Bundle\RequestHandler\Factory\SymfonyControllerRequestHandlerFactory;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SymfonyControllerAdapter
{
    /** @var HttpFoundationFactoryInterface */
    private $foundationHttpFactory;

    /** @var HttpMessageFactoryInterface */
    private $psrRequestFactory;

    /** @var RequestMiddlewareResolverInterface */
    private $httpMiddlewareResolver;

    /** @var SymfonyControllerRequestHandlerFactory */
    private $controllerRequestHandlerFactory;

    /** @var Request */
    private $request;

    /** @var callable */
    private $originalController;

    /** @var array */
    private $controllerArguments;

    public function __construct(
        RequestMiddlewareResolverInterface $httpMiddlewareResolver,
        HttpFoundationFactoryInterface $foundationFactory,
        HttpMessageFactoryInterface $psrRequestFactory,
        SymfonyControllerRequestHandlerFactory $controllerRequestHandlerFactory
    ) {
        $this->httpMiddlewareResolver          = $httpMiddlewareResolver;
        $this->foundationHttpFactory           = $foundationFactory;
        $this->psrRequestFactory               = $psrRequestFactory;
        $this->controllerRequestHandlerFactory = $controllerRequestHandlerFactory;
    }

    public function setOriginalResources(callable $originalController, Request $request, array $controllerArguments): self
    {
        $this->originalController  = $originalController;
        $this->request             = $request;
        $this->controllerArguments = $controllerArguments;

        return $this;
    }

    public function __invoke(): Response
    {
        $middlewareChain = $this->httpMiddlewareResolver->resolveMiddlewareChain($this->request);
        $psrRequest      = $this->psrRequestFactory->createRequest($this->request);
        $psrResponse     = $middlewareChain->process(
            $psrRequest,
            $this->controllerRequestHandlerFactory->create(
                $this->originalController,
                $this->controllerArguments
            )
        );

        return $this->foundationHttpFactory->createResponse($psrResponse);
    }
}