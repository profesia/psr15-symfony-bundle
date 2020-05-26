<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;

class HttpMethod extends AbstractHttpMethod
{
    /**
     * @param AbstractMiddlewareChainItem[] $methodsConfig
     *
     * @return AbstractMiddlewareChainItem|null
     */
    public function extractMiddleware(array $methodsConfig): ?AbstractMiddlewareChainItem
    {
        return $methodsConfig[$this->getValue()] ?? null;
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function toString(): string
    {
        return (string)$this;
    }
}