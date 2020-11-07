<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver;

use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\AbstractResolveException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class RequestMiddlewareResolver implements RequestMiddlewareResolverInterface
{
    private AbstractChainResolver  $middlewareResolverChain;
    private ?LoggerInterface       $logger;

    public function __construct(AbstractChainResolver $middlewareResolverChain, ?LoggerInterface $logger = null)
    {
        $this->middlewareResolverChain = $middlewareResolverChain;
        $this->logger                  = $logger;
    }

    public function resolveMiddlewareChain(MiddlewareResolvingRequest $request): ResolvedMiddlewareChain
    {
        if ($request->hasAccessKey()) {
            $accessKey       = $request->getAccessKey();
            $middlewareChain = null;
            try {
                $middlewareChain = $this->middlewareResolverChain->getChain(
                    $request->getAccessKey()
                );
            } catch (AbstractResolveException $e) {
                $this->log(
                    LogLevel::WARNING,
                    "Unable to fetch cached resolver. Cause: [{$e->getMessage()}]",
                    [
                        'accessKey' => $accessKey->toArray()
                    ]
                );
            }

            if ($middlewareChain !== null && $accessKey !== null) {
                $cachedMiddleware = ResolvedMiddlewareChain::createFromResolverContext(
                    $middlewareChain,
                    $accessKey
                );

                $this->log(
                    LogLevel::INFO,
                    'Fetched middleware chain from cache',
                    [
                        'accessKey'       => $accessKey->toArray(),
                        'middlewareChain' => $middlewareChain->listChainClassNames()
                    ]
                );

                return $cachedMiddleware;
            }
        }

        $resolvedMiddleware = $this->middlewareResolverChain->handle(
            $request
        );

        if (!$resolvedMiddleware->isNullMiddleware()) {
            $this->log(
                LogLevel::INFO,
                'Resolved middleware chain',
                [
                    'accessKey'       =>
                        $resolvedMiddleware
                            ->getMiddlewareAccessKey()
                            ->toArray(),
                    'middlewareChain' =>
                        $resolvedMiddleware
                            ->getMiddlewareChain()
                            ->listChainClassNames()
                ]
            );
        }

        return $resolvedMiddleware;
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->log($level, $message, $context);
    }
}