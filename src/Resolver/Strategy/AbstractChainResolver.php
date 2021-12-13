<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Strategy;

use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\AbstractResolveException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\ChainNotFoundException;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;

abstract class AbstractChainResolver
{
    private ?AbstractChainResolver $next = null;

    public function setNext(AbstractChainResolver $chainItem): self
    {
        $this->next = $chainItem;

        return $this;
    }

    public abstract function handle(MiddlewareResolvingRequest $request): ResolvedMiddlewareChain;

    public abstract function getChain(ResolvedMiddlewareAccessKey $accessKey): ResolvedMiddlewareChain;

    /**
     * @return ExportedMiddleware[]
     */
    public abstract function exportRules(): array;

    protected function handleNext(MiddlewareResolvingRequest $request): ResolvedMiddlewareChain
    {
        if ($this->next === null) {
            return ResolvedMiddlewareChain::createDefault();
        }

        return $this->next->handle($request);
    }

    /**
     * @param ResolvedMiddlewareAccessKey $accessKey
     *
     * @return ResolvedMiddlewareChain
     * @throws AbstractResolveException
     */
    protected function getChainNext(ResolvedMiddlewareAccessKey $accessKey): ResolvedMiddlewareChain
    {
        if ($this->next === null) {
            throw new ChainNotFoundException('No resolver was able to retrieve middleware chain');
        }

        return $this->next->getChain($accessKey);
    }
}
