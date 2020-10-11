<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\ValueObject;

use InvalidArgumentException;

abstract class AbstractHttpMethod implements HttpMethodInterface
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

    protected final function __construct(string $value)
    {
        $this->value = $value;
    }

    protected function getValue(): string
    {
        return $this->value;
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
            throw new InvalidArgumentException("String: [{$value}] is not a valid value for HTTP method");
        }

        return new static($upperCasedValue);
    }

    public function equals(AbstractHttpMethod $methodToCompare): bool
    {
        return ($this->value === $methodToCompare->getValue());
    }
}