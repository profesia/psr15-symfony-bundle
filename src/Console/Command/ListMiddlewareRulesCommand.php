<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Console\Command;

use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathStrategyResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameStrategyResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListMiddlewareRulesCommand extends Command
{
    private RouteNameStrategyResolver    $routeNameStrategyResolver;
    private CompiledPathStrategyResolver $compiledPathStrategyResolver;

    public function __construct(RouteNameStrategyResolver $routeNameStrategyResolver, CompiledPathStrategyResolver $compiledPathStrategyResolver)
    {
        $this->routeNameStrategyResolver    = $routeNameStrategyResolver;
        $this->compiledPathStrategyResolver = $compiledPathStrategyResolver;

        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->setName('profesia:psr15:middleware:list-rules')
            ->setDescription('Lists all registered middleware chains routing rules')
            ->setHelp('This commands lists all the registered routing rules for middleware chains');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaderTitle('Route rules');
        $table->setHeaders(['Route name', 'HTTP method', 'Middleware list']);
        static::fillTableWithMiddlewareData(
            $table,
            $this->routeNameStrategyResolver->exportRules()
        );
        $table->render();

        $table = new Table($output);
        $table->setHeaderTitle('Path rules');
        $table->setHeaders(['Path pattern', 'HTTP Method', 'Middleware chain']);
        static::fillTableWithMiddlewareData(
            $table,
            $this->compiledPathStrategyResolver->exportRules()
        );
        $table->render();

        return 1;
    }

    /**
     * @param Table                $table
     * @param ExportedMiddleware[] $exportedRules
     */
    private static function fillTableWithMiddlewareData(Table $table, array $exportedRules): void
    {
        $index       = 1;
        $sizeOfTable = sizeof($exportedRules);
        foreach ($exportedRules as $exportedMiddleware) {
            $table->addRow(
                [
                    $exportedMiddleware->getIdentifier(),
                    $exportedMiddleware->getHttpMethods()->listMethods(' | '),
                    implode(
                        "\n",
                        $exportedMiddleware->listMiddlewareChainItems()
                    )
                ]
            );

            if ($index !== $sizeOfTable) {
                $table->addRow(new TableSeparator());
            }

            $index++;
        }
    }
}