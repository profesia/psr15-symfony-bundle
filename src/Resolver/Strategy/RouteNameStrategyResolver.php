<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use RuntimeException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouteNameStrategyResolver extends AbstractChainResolverItem
{
    /** @var array AbstractMiddlewareChainItem */
    private $registeredRouteMiddlewares = [];

    /** @var RouteCollection */
    private $routeCollection;

    public function __construct(MiddlewareChainItemFactory $middlewareChainItemFactory, RouterInterface $router)
    {
        parent::__construct($middlewareChainItemFactory);
        $this->routeCollection = $router->getRouteCollection();
    }

    public function registerRouteMiddleware(string $routeName, AbstractMiddlewareChainItem $middlewareChain): self
    {
        if (array_key_exists($routeName, $this->registeredRouteMiddlewares)) {
            return $this;
        }

        if ($this->routeCollection->get($routeName) === null) {
            throw new RuntimeException("Route with name: [{$routeName}] is not registered");
        }

        $this->registeredRouteMiddlewares[$routeName] = $middlewareChain;

        return $this;
    }

    public function handle(MiddlewareResolvingRequest $request): AbstractMiddlewareChainItem
    {
        dump($this->registeredRouteMiddlewares);
        exit;
        $routeName = $request->getRouteName();
        if (isset($this->registeredRouteMiddlewares[$routeName])) {
            return $this->registeredRouteMiddlewares[$routeName];
        }

        if (isset($this->registeredRouteMiddlewares['*'])) {
            return $this->registeredRouteMiddlewares['*'];
        }

        return $this->handleNext($request);
    }
}