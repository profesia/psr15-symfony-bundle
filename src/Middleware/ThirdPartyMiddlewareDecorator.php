<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Middleware;


use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ThirdPartyMiddlewareDecorator extends AbstractMiddlewareChainItem
{
    private MiddlewareInterface $decoratedMiddleware;

    public function __construct(
        ServerRequestFactoryInterface $serverRequestFactory,
        ResponseFactoryInterface $responseFactory,
        MiddlewareInterface $decoratedMiddleware
    ) {
        $this->decoratedMiddleware = $decoratedMiddleware;
        parent::__construct($serverRequestFactory, $responseFactory);
    }


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->decoratedMiddleware->process($request, $handler);
    }
}
