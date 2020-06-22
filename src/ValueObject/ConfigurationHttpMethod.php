<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;

class ConfigurationHttpMethod extends AbstractHttpMethod
{
    private const METHOD_ANY = 'ANY';

    public static function createDefault(): self
    {
        return new self(static::METHOD_ANY);
    }

    /**
     * @param AbstractMiddlewareChainItem $middlewareChain
     *
     * @return AbstractMiddlewareChainItem[]
     */
    public function assignMiddlewareChainToHttpMethods(AbstractMiddlewareChainItem $middlewareChain): array
    {
        if ($this->getValue() !== static::METHOD_ANY) {
            return [
                $this->toString() => $middlewareChain
            ];
        }

        $possibleValues          = parent::getPossibleValues();
        $assignedMiddlewareItems = [];
        foreach ($possibleValues as $possibleValue) {
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
        return $this->getValue();
    }

    /**
     * @return string[]
     */
    public static function getPossibleValues(): array
    {
        return array_merge(
            parent::getPossibleValues(),
            [
                self::METHOD_ANY
            ]
        );
    }
}