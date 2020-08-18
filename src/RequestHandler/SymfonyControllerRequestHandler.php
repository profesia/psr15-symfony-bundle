<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\RequestHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SymfonyControllerRequestHandler implements RequestHandlerInterface
{
    /** @var HttpFoundationFactoryInterface */
    private $foundationHttpFactory;

    /** @var HttpMessageFactoryInterface */
    private $psrHttpFactory;

    /** @var RequestStack */
    private $requestStack;

    /** @var callable */
    private $symfonyCallable;

    /** @var array */
    private $symfonyCallableArguments;

    public function __construct(
        HttpFoundationFactoryInterface $foundationHttpFactory,
        HttpMessageFactoryInterface $psrHttpFactory,
        RequestStack $requestStack,
        callable $symfonyCallable,
        array $symfonyCallableArguments
    ) {
        $this->foundationHttpFactory    = $foundationHttpFactory;
        $this->psrHttpFactory           = $psrHttpFactory;
        $this->requestStack             = $requestStack;
        $this->symfonyCallable          = $symfonyCallable;
        $this->symfonyCallableArguments = $symfonyCallableArguments;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $symfonyRequest = $this->foundationHttpFactory->createRequest($request);
        $requests       = [];
        while (($request = $this->requestStack->pop()) !== null) {
            $requests[] = $request;
        }

        $requests[sizeof($requests) - 1] = $symfonyRequest;
        foreach ($requests as $request) {
            $this->requestStack->push($request);
        }

        $symfonyResponse = call_user_func_array(
            $this->symfonyCallable,
            array_map(
                function ($item) use ($symfonyRequest) {
                    if ($item instanceof Request) {
                        return $symfonyRequest;
                    }

                    return $item;
                },
                $this->symfonyCallableArguments
            )
        );

        return $this->psrHttpFactory->createResponse($symfonyResponse);
    }
}