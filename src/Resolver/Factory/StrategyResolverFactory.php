<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver\Factory;

use Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolverItem;

class StrategyResolverFactory
{
    /**
     * @param AbstractChainResolverItem[] $strategyResolverItems
     *
     * @return AbstractChainResolverItem
     */
    public function create(array $strategyResolverItems): AbstractChainResolverItem
    {
        /** @var AbstractChainResolverItem $first */
        $first = null;

        /** @var AbstractChainResolverItem $previous */
        $previous = null;
        foreach ($strategyResolverItems as $strategyResolverItem) {
            if ($first === null) {
                $first = $strategyResolverItem;
            }

            if ($previous !== null) {
                $previous->setNext($strategyResolverItem);
            }

            $previous = $strategyResolverItem;
        }

        return $first;
    }
}