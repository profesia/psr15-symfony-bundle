<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Strategy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Profesia\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\AbstractResolveException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\ChainNotFoundException;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use RuntimeException;

abstract class AbstractChainResolver
{
    private MiddlewareChainItemFactory $middlewareChainItemFactory;
    private ?AbstractChainResolver     $next = null;

    public function __construct(MiddlewareChainItemFactory $middlewareChainItemFactory)
    {
        $this->middlewareChainItemFactory = $middlewareChainItemFactory;
    }

    public function setNext(AbstractChainResolver $chainItem): self
    {
        $this->next = $chainItem;

        return $this;
    }

    public abstract function handle(MiddlewareResolvingRequest $request): ResolvedMiddlewareChain;

    /**
     * @param ResolvedMiddlewareAccessKey $accessKey
     *
     * @return AbstractMiddlewareChainItem
     * @throws RuntimeException
     */
    public abstract function getChain(ResolvedMiddlewareAccessKey $accessKey): AbstractMiddlewareChainItem;

    /**
     * @return ExportedMiddleware[]
     */
    public abstract function exportRules(): array;

    protected function handleNext(MiddlewareResolvingRequest $request): ResolvedMiddlewareChain
    {
        if ($this->next === null) {
            return ResolvedMiddlewareChain::createDefault(
                $this->middlewareChainItemFactory->createNullChainItem()
            );
        }

        return $this->next->handle($request);
    }

    /**
     * @param ResolvedMiddlewareAccessKey $accessKey
     *
     * @return AbstractMiddlewareChainItem
     * @throws AbstractResolveException
     */
    protected function getChainNext(ResolvedMiddlewareAccessKey $accessKey): AbstractMiddlewareChainItem
    {
        if ($this->next === null) {
            throw new ChainNotFoundException('No resolver was able to retrieve middleware chain');
        }

        return $this->next->getChain($accessKey);
    }
}