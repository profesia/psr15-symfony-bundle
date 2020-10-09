<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use InvalidArgumentException;

final class ConfigurationHttpMethod implements HttpMethodInterface
{
    private const METHOD_ANY = 'ANY';

    private static array $possibleValues = [];
    private array        $values;

    private function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function createDefault(): ConfigurationHttpMethod
    {
        return new self(
            [
                static::METHOD_ANY
            ]
        );
    }

    public static function createFromString(string $value): ConfigurationHttpMethod
    {
        $explodedValues = explode('|', $value);
        $possibleValues = static::getPossibleValues();
        foreach ($explodedValues as $method) {
            if (!in_array($method, $possibleValues)) {
                throw new InvalidArgumentException("Value: [{$method}] is not supported");
            }

            if ($method === static::METHOD_ANY && sizeof($explodedValues) > 1) {
                throw new InvalidArgumentException("HTTP method configuration is not valid. In case of 'ANY' any other HTTP methods are redundant");
            }
        }

        return new self(
            $explodedValues
        );
    }

    /**
     * @return string[]
     */
    public static function getPossibleValues(): array
    {
        if (static::$possibleValues === []) {
            static::$possibleValues = array_merge(
                AbstractHttpMethod::getPossibleValues(),
                [
                    self::METHOD_ANY
                ]
            );
        }

        return static::$possibleValues;
    }

    /**
     * @param AbstractMiddlewareChainItem $middlewareChain
     *
     * @return AbstractMiddlewareChainItem[]
     */
    public function assignMiddlewareChainToHttpMethods(AbstractMiddlewareChainItem $middlewareChain): array
    {
        if ($this->toString() !== static::METHOD_ANY) {
            $returnMap = [];
            foreach ($this->values as $value) {
                $returnMap[$value] = $middlewareChain;
            }

            return $returnMap;
        }

        $possibleValues          = static::getPossibleValues();
        $assignedMiddlewareItems = [];
        foreach ($possibleValues as $possibleValue) {
            if ($possibleValue === static::METHOD_ANY) {
                continue;
            }

            $assignedMiddlewareItems[$possibleValue] = $middlewareChain;
        }
        

        return $assignedMiddlewareItems;
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
}