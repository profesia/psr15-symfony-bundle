<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Adapter;

use Delvesoft\Symfony\Psr15Bundle\RequestHandler\SymfonyControllerRequestHandler;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class SymfonyControllerAdapter
{
    /** @var RequestStack */
    private $requestStack;

    /** @var HttpFoundationFactoryInterface */
    private $foundationHttpFactory;

    /** @var HttpMessageFactoryInterface */
    private $psrRequestFactory;

    /** @var RequestMiddlewareResolverInterface */
    private $httpMiddlewareResolver;

    /** @var callable */
    private $originalController;

    /** @var Request */
    private $request;

    /** @var array */
    private $controllerArguments;

    public function __construct(
        RequestMiddlewareResolverInterface $httpMiddlewareResolver,
        RequestStack $requestStack,
        HttpFoundationFactoryInterface $foundationFactory,
        HttpMessageFactoryInterface $psrRequestFactory
    ) {
        $this->requestStack           = $requestStack;
        $this->httpMiddlewareResolver = $httpMiddlewareResolver;
        $this->foundationHttpFactory  = $foundationFactory;
        $this->psrRequestFactory      = $psrRequestFactory;
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
        $handler = SymfonyControllerRequestHandler::createFromObjects(
            $this->foundationHttpFactory,
            $this->psrRequestFactory,
            $this->requestStack,
            $this->originalController,
            $this->controllerArguments
        );

        $middlewareChain = $this->httpMiddlewareResolver->resolveMiddlewareChain($this->request);
        $psrRequest      = $this->psrRequestFactory->createRequest($this->request);
        $psrResponse     = $middlewareChain->process($psrRequest, $handler);

        return $this->foundationHttpFactory->createResponse($psrResponse);
    }
}