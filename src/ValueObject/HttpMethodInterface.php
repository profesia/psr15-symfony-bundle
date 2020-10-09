<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\ValueObject;

interface HttpMethodInterface
{
    public static function getPossibleValues(): array;
    public static function createFromString(string $value): HttpMethodInterface;
    public function toString(): string;
}