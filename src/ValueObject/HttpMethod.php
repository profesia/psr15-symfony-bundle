<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use InvalidArgumentException;

class HttpMethod implements HttpMethodInterface
{
    private const METHOD_GET     = 'GET';
    private const METHOD_POST    = 'POST';
    private const METHOD_PUT     = 'PUT';
    private const METHOD_DELETE  = 'DELETE';
    private const METHOD_HEAD    = 'HEAD';
    private const METHOD_CONNECT = 'CONNECT';
    private const METHOD_OPTIONS = 'OPTIONS';
    private const METHOD_TRACE   = 'TRACE';
    private const METHOD_PATCH   = 'PATCH';

    protected string $value;

    private final function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string[]
     */
    public static function getPossibleValues(): array
    {
        return [
            self::METHOD_GET,
            self::METHOD_POST,
            self::METHOD_PUT,
            self::METHOD_DELETE,
            self::METHOD_HEAD,
            self::METHOD_CONNECT,
            self::METHOD_OPTIONS,
            self::METHOD_TRACE,
            self::METHOD_PATCH,
        ];
    }

    /**
     * @param string $value
     *
     * @return static
     */
    public static function createFromString(string $value): self
    {
        $upperCasedValue = strtoupper($value);
        if (!in_array($upperCasedValue, static::getPossibleValues())) {
            throw new InvalidArgumentException("String: [{$value}] is not a valid value for HttpMethod");
        }

        return new static($upperCasedValue);
    }

    public function equals(HttpMethod $methodToCompare): bool
    {
        return ($this->value === $methodToCompare->getValue());
    }

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

    private function getValue(): string
    {
        return $this->value;
    }
}