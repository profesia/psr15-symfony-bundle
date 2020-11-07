<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Factory;

use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolver;

class StrategyResolverFactory
{
    /**
     * @param AbstractChainResolver[] $strategyResolverItems
     *
     * @return AbstractChainResolver
     */
    public function create(array $strategyResolverItems): AbstractChainResolver
    {
        /** @var AbstractChainResolver $first */
        $first = null;

        /** @var AbstractChainResolver $previous */
        $previous = null;
        foreach ($strategyResolverItems as $strategyResolverItem) {
            if (!($first instanceof AbstractChainResolver)) {
                $first = $strategyResolverItem;
            }

            if ($previous instanceof AbstractChainResolver) {
                $previous->setNext($strategyResolverItem);
            }

            $previous = $strategyResolverItem;
        }

        return $first;
    }
}