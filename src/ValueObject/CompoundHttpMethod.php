<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\ValueObject;

class CompoundHttpMethod
{
    /** @var HttpMethod[] */
    private array $httpMethods;

    private function __construct(array $httpMethods)
    {
        $this->httpMethods = $httpMethods;
    }

    /**
     * @param array<int, string> $httpMethods
     *
     * @return CompoundHttpMethod
     */
    public static function createFromStrings(array $httpMethods): CompoundHttpMethod
    {
        $methodValueObjects = [];
        foreach ($httpMethods as $httpMethod) {
            $httpMethodObject                                  = HttpMethod::createFromString($httpMethod);
            $methodValueObjects[$httpMethodObject->toString()] = $httpMethodObject;
        }

        return new self($methodValueObjects);
    }

    public function listMethods(string $delimiter): string
    {
        return implode(
            $delimiter,
            array_map(
                function (HttpMethod $httpMethod) {
                    return $httpMethod->toString();
                },
                $this->httpMethods
            )
        );
    }

    public function isEmpty(): bool
    {
        return ($this->httpMethods === []);
    }
}