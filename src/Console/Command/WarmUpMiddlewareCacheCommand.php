<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Console\Command;

use Delvesoft\Symfony\Psr15Bundle\Resolver\Proxy\HttpRequestMiddlewareResolverProxy;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class WarmUpMiddlewareCacheCommand extends Command
{
    /** @var Route[] */
    private $routes;

    /** @var HttpRequestMiddlewareResolverProxy */
    private $resolverCacheProxy;

    public function __construct(RouterInterface $router, HttpRequestMiddlewareResolverProxy $resolverCacheProxy)
    {
        $this->routes             = array_filter(
            $router->getRouteCollection()->all(),
            function (Route $route) {
                return !(strpos($route->getPath(), '/_') === 0);
            }
        );
        $this->resolverCacheProxy = $resolverCacheProxy;

        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName('psr15:middleware:warm-up')
            ->setDescription('Warms up middleware cache')
            ->setHelp('The command iterates over registered routes and resolves middleware for every registered route');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['Route', 'HTTP method', 'Middleware chain items']);
        $index = 1;
        foreach ($this->routes as $routeName => $route) {
            $innerIndex = 1;
            foreach ($route->getMethods() as $httpMethod) {
                $request = new Request();
                $request->attributes->set('_route', $routeName);
                $request->setMethod($httpMethod);

                $middleware = $this->resolverCacheProxy->resolveMiddlewareChain($request);
                $table->addRow([$routeName, $httpMethod, implode("\n", $middleware->listChainClassNames())]);

                if ($innerIndex !== sizeof($route->getMethods())) {
                    $table->addRow(new TableSeparator());
                }

                $innerIndex++;
            }

            if ($index !== sizeof($this->routes)) {
                $table->addRow(new TableSeparator());
            }

            $index++;
        }

        $table->render();

        return 1;
    }
}