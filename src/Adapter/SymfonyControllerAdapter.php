<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Adapter;

use Delvesoft\Symfony\Psr15Bundle\RequestHandler\SymfonyControllerRequestHandler;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SymfonyControllerAdapter
{
    /** @var ContainerInterface */
    private $container;

    /** @var HttpFoundationFactoryInterface */
    private $foundationHttpFactory;

    /** @var HttpMessageFactoryInterface */
    private $psrHttpFactory;

    /** @var RequestMiddlewareResolverInterface */
    private $httpMiddlewareResolver;

    /** @var callable */
    private $originalController;

    /** @var Request */
    private $request;

    public function __construct(ContainerInterface $container, RequestMiddlewareResolverInterface $httpMiddlewareResolver)
    {
        $this->container = $container;
        $this->httpMiddlewareResolver = $httpMiddlewareResolver;
        $this->foundationHttpFactory = new HttpFoundationFactory();
        $psr17Factory                = new Psr17Factory();
        $this->psrHttpFactory        = new PsrHttpFactory(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );
    }

    public function setOriginalResources(callable $originalController, Request $request): self
    {
        $this->originalController = $originalController;
        $this->request            = $request;

        return $this;
    }

    public function __invoke(): Response
    {
        dump($this->container);
        exit;
        $handler = SymfonyControllerRequestHandler::createFromObjects(
            $this->foundationHttpFactory,
            $this->psrHttpFactory,
            $this->originalController,
            func_get_args()
        );

        $middlewareChain = $this->httpMiddlewareResolver->resolveMiddlewareChain($this->request);
        $psrRequest      = $this->psrHttpFactory->createRequest($this->request);
        $psrResponse     = $middlewareChain->process($psrRequest, $handler);

        return $this->foundationHttpFactory->createResponse($psrResponse);
    }
}