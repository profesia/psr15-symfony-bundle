<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\RequestHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;

class SymfonyControllerRequestHandler implements RequestHandlerInterface
{
    /** @var HttpFoundationFactoryInterface */
    private $foundationHttpFactory;

    /** @var HttpMessageFactoryInterface */
    private $psrHttpFactory;

    /** @var callable */
    private $symfonyCallable;

    /** @var array */
    private $symfonyCallableArguments = [];

    private function __construct(
        HttpFoundationFactoryInterface $foundationHttpFactory,
        HttpMessageFactoryInterface $psrHttpFactory,
        callable $symfonyCallable,
        array $symfonyCallableArguments
    ) {
        $this->foundationHttpFactory    = $foundationHttpFactory;
        $this->psrHttpFactory           = $psrHttpFactory;
        $this->symfonyCallable          = $symfonyCallable;
        $this->symfonyCallableArguments = $symfonyCallableArguments;
    }


    public static function createFromObjects(
        HttpFoundationFactoryInterface $foundationHttpFactory,
        HttpMessageFactoryInterface $psrHttpFactory,
        callable $symfonyCallable,
        array $symfonyCallableArguments = []
    ): self {
        return new self($foundationHttpFactory, $psrHttpFactory, $symfonyCallable, $symfonyCallableArguments);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $symfonyRequest                    = $this->foundationHttpFactory->createRequest($request);
        $this->symfonyCallableArguments[0] = $symfonyRequest;
        $symfonyResponse                   = call_user_func_array(
            $this->symfonyCallable,
            $this->symfonyCallableArguments
        );

        return $this->psrHttpFactory->createResponse($symfonyResponse);
    }
}