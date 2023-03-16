<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\RequestHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SymfonyControllerRequestHandler implements RequestHandlerInterface
{
    private HttpFoundationFactoryInterface $foundationHttpFactory;
    private HttpMessageFactoryInterface $psrHttpFactory;
    private RequestStack $requestStack;
    private array $symfonyCallableArguments;

    /** @var callable */
    private $symfonyCallable;

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
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest !== null && $currentRequest->hasSession()) {
            $symfonyRequest->setSession(
                $currentRequest->getSession()
            );
        }

        $requests = [];
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
