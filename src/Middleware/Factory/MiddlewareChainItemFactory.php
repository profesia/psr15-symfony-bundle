<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Middleware\Factory;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

class MiddlewareChainItemFactory
{
    private ServerRequestFactoryInterface $serverRequestFactory;
    private ResponseFactoryInterface      $responseFactory;

    public function __construct(ServerRequestFactoryInterface $serverRequestFactory, ResponseFactoryInterface $responseFactory)
    {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->responseFactory      = $responseFactory;
    }

    public function createInstance(string $class): AbstractMiddlewareChainItem
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class: [{$class}] does not exist");
        }

        return new $class(
            $this->serverRequestFactory,
            $this->responseFactory
        );
    }

    public function createNullChainItem(): AbstractMiddlewareChainItem
    {
        return $this->createInstance(NullMiddleware::class);
    }
}