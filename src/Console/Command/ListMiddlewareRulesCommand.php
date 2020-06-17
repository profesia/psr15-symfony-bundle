<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Console\Command;

use Delvesoft\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListMiddlewareRulesCommand extends Command
{
    /** @var array[] */
    private $registeredRouteMiddlewares = [];

    /** @var array */
    private $registeredPathMiddlewares = [];

    public function registerPathMiddleware(ConfigurationPath $path, array $middlewareChains, string $middlewareChainName): self
    {
        /*$exportedConfiguration = $path->exportConfigurationForMiddleware($middlewareChain);
        foreach ($exportedConfiguration as $pathLength => $registeredPaths) {
            if (!isset($this->registeredPathMiddlewares[$pathLength])) {
                $this->registeredPathMiddlewares[$pathLength] = [];
            }

            foreach ($registeredPaths as $path => $pathConfiguration) {
                if (!isset($this->registeredPathMiddlewares[$pathLength][$path])) {
                    $this->registeredPathMiddlewares[$pathLength][$path] = [];
                }

                foreach ($pathConfiguration as $method => $middlewareChain) {
                    if (isset($this->registeredPathMiddlewares[$pathLength][$path][$method])) {
                        continue;
                    }

                    $this->registeredPathMiddlewares[$pathLength][$path][$method] = $middlewareChain;
                }
            }
        }*/

        return $this;
    }

    public function registerRouteMiddleware(string $routeName, array $middlewareChain, string $middlewareChainName): self
    {
        if (array_key_exists($routeName, $this->registeredRouteMiddlewares)) {
            return $this;
        }

        $this->registeredRouteMiddlewares[$routeName] = [
            'groupName' => $middlewareChainName,
            'chain'     => $middlewareChain
        ];

        return $this;
    }

    protected function configure()
    {
        $this
            ->setName('psr15:middleware:list-rules')
            ->setDescription('Lists all registered middleware rules')
            ->setHelp('This commands lists all the registered rules for middlewares');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaderTitle('Route rules');
        $table->setHeaders(['Route name', 'Middleware chain name', 'Middleware list']);
        $index = 1;
        $sizeOfTable = sizeof($this->registeredRouteMiddlewares);
        foreach ($this->registeredRouteMiddlewares as $routeName => $routeConfig) {
            $middlewareChainName = $routeConfig['groupName'];
            $table->addRow([$routeName, $middlewareChainName, implode("\n", $routeConfig['chain'])]);
            if ($index !== $sizeOfTable) {
                $table->addRow(new TableSeparator());
            }

            $index++;
        }
        $table->render();

        $table = new Table($output);
        $table->setHeaderTitle('Path rules');
        $table->setHeaders(['Path', 'HTTP Method', 'Middleware chain name', 'Middleware list']);
        $table->render();

        return 1;
    }
}