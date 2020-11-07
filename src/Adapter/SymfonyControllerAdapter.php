<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Adapter;

use Profesia\Symfony\Psr15Bundle\RequestHandler\Factory\SymfonyControllerRequestHandlerFactory;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use RuntimeException;
use Symfony\Component\Routing\RouterInterface;

class SymfonyControllerAdapter
{
    private HttpFoundationFactoryInterface           $foundationHttpFactory;
    private HttpMessageFactoryInterface              $psrRequestFactory;
    private RequestMiddlewareResolverInterface       $httpMiddlewareResolver;
    private SymfonyControllerRequestHandlerFactory   $controllerRequestHandlerFactory;
    private Request                                  $request;
    private RouteCollection                          $routeCollection;
    private array                                    $controllerArguments;

    /** @var callable */
    private $originalController;


    public function __construct(
        RequestMiddlewareResolverInterface $httpMiddlewareResolver,
        HttpFoundationFactoryInterface $foundationFactory,
        HttpMessageFactoryInterface $psrRequestFactory,
        RouterInterface $router,
        SymfonyControllerRequestHandlerFactory $controllerRequestHandlerFactory
    ) {
        $this->httpMiddlewareResolver          = $httpMiddlewareResolver;
        $this->foundationHttpFactory           = $foundationFactory;
        $this->psrRequestFactory               = $psrRequestFactory;
        $this->controllerRequestHandlerFactory = $controllerRequestHandlerFactory;
        $this->routeCollection                 = $router->getRouteCollection();
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
        $routeName = $this->request->attributes->get('_route');
        $route     = $this->routeCollection->get($routeName);
        if ($route === null) {
            throw new RuntimeException("Route: [{$routeName}] is not registered");
        }

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            $this->request,
            $route,
            $routeName
        );

        $resolvedMiddlewareChain = $this->httpMiddlewareResolver->resolveMiddlewareChain($middlewareResolvingRequest);
        $psrRequest              = $this->psrRequestFactory->createRequest($this->request);
        $psrResponse             = $resolvedMiddlewareChain
            ->getMiddlewareChain()
            ->process(
                $psrRequest,
                $this->controllerRequestHandlerFactory->create(
                    $this->originalController,
                    $this->controllerArguments
                )
            );

        return $this->foundationHttpFactory->createResponse($psrResponse);
    }
}