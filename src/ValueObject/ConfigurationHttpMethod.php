<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use InvalidArgumentException;

final class ConfigurationHttpMethod implements HttpMethodInterface
{
    private array $values;

    private function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function createDefault(): ConfigurationHttpMethod
    {
        return new self(
            static::getPossibleValues()
        );
    }

    public static function createFromArray(array $values): ConfigurationHttpMethod
    {
        static::validateAllValues($values);

        return new self(
            $values
        );
    }

    public static function createFromString(string $value): ConfigurationHttpMethod
    {
        return new self(
            static::validateAndSplit($value)
        );
    }

    /**
     * @param string $value
     *
     * @return string[]
     */
    public static function validateAndSplit(string $value): array
    {
        $explodedValues = explode('|', $value);
        static::validateAllValues($explodedValues);

        return $explodedValues;
    }

    /**
     * @return string[]
     */
    public static function getPossibleValues(): array
    {
        return HttpMethod::getPossibleValues();
    }

    /**
     * @param array<string, AbstractMiddlewareChainItem> $middlewareChains
     *
     * @return array<string, AbstractMiddlewareChainItem>
     */
    public function assignMiddlewareChainToHttpMethods(array $middlewareChains): array
    {
        foreach ($this->values as $value) {
            if (!isset($middlewareChains[$value])) {
                throw new InvalidArgumentException("Value: [{$value}] is not present in the middleware chain array");
            }
        }

        return $middlewareChains;
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function toString(): string
    {
        return (string)$this;
    }

    public function __toString(): string
    {
        return implode(
            '|',
            $this->getValue()
        );
    }

    private function getValue(): array
    {
        return $this->values;
    }

    private static function validateAllValues(array $values): void
    {
        $possibleValues = static::getPossibleValues();
        foreach ($values as $method) {
            if (!in_array($method, $possibleValues)) {
                throw new InvalidArgumentException("Value: [{$method}] is not supported");
            }
        }
    }
}