<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\ValueObject;

class CompoundHttpMethod
{
    /** @var HttpMethod[] */
    private $httpMethods;

    /**
     * @var HttpMethod[]
     */
    private function __construct(array $httpMethods)
    {
        $this->httpMethods = $httpMethods;
    }

    public static function createFromStrings(array $httpMethods): self
    {
        $methodValueObjects = [];
        foreach ($httpMethods as $httpMethod) {
            $methodValueObjects[$httpMethod] = HttpMethod::createFromString($httpMethod);
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
}