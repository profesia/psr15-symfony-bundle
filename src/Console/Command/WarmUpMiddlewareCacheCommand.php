<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Console\Command;

use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverCachingInterface;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\HttpMethod;
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
    private array                                     $routes;
    private RequestMiddlewareResolverCachingInterface $resolverCacheProxy;

    public function __construct(RouterInterface $router, RequestMiddlewareResolverCachingInterface $resolverCacheProxy)
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


    protected function configure(): void
    {
        $this
            ->setName('psr15:middleware:warm-up')
            ->setDescription('Warms up middleware cache')
            ->setHelp('The command iterates over registered routes and resolves and caches middleware chain for each registered route ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['Route', 'Static Path', 'HTTP method', 'Middleware chain items']);
        $index = 1;
        foreach ($this->routes as $routeName => $route) {
            $innerIndex   = 1;
            $staticPrefix = $route->compile()->getStaticPrefix();
            $httpMethods  = $route->getMethods();
            if ($httpMethods === []) {
                $httpMethods = HttpMethod::getPossibleValues();
            }

            foreach ($httpMethods as $httpMethod) {
                $request = new Request();
                $request->attributes->set('_route', $routeName);
                $request->setMethod($httpMethod);

                $middleware = $this->resolverCacheProxy->resolveMiddlewareChain($request);
                $table->addRow([$routeName, $staticPrefix, $httpMethod, implode("\n", $middleware->listChainClassNames())]);

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