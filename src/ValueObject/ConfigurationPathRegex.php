<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\ValueObject;

use InvalidArgumentException;

class ConfigurationPathRegex
{
    /** @var string */
    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function createFromString(string $value): self
    {
        $regexLength = strlen($value);
        if ($regexLength < 2 || ($regexLength >= 2 && strpos($value, '\/') !== 0)) {
            throw new InvalidArgumentException("Path should be a string composed at least of '\/' characters");
        }

        return new self($value);
    }

    public function calculateRegexDepth(): int
    {
        return count(
            explode(
                '\/',
                $this->value
            )
        );
    }
}