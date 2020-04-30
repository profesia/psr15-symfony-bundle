<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\RequestHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SymfonyKernelRequestHandler implements RequestHandlerInterface
{
    /** @var HttpFoundationFactoryInterface */
    private $foundationHttpFactory;

    /** @var HttpMessageFactoryInterface */
    private $psrHttpFactory;

    /** @var HttpKernelInterface */
    private $kernel;

    /** @var int */
    private $type;

    /** @var bool */
    private $catch;

    private function __construct(
        HttpFoundationFactoryInterface $foundationHttpFactory,
        HttpMessageFactoryInterface $psrHttpFactory,
        HttpKernelInterface $kernel,
        int $type,
        bool $catch
    ) {
        $this->foundationHttpFactory = $foundationHttpFactory;
        $this->psrHttpFactory        = $psrHttpFactory;
        $this->kernel                = $kernel;
        $this->type                  = $type;
        $this->catch                 = $catch;
    }

    public static function createFromObjects(
        HttpFoundationFactoryInterface $foundationHttpFactory,
        HttpMessageFactoryInterface $psrHttpFactory,
        HttpKernelInterface $kernel,
        int $type,
        bool $catch
    ): self {
        return new self(
            $foundationHttpFactory,
            $psrHttpFactory,
            $kernel,
            $type,
            $catch
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $symfonyRequest  = $this->foundationHttpFactory->createRequest($request);
        $symfonyResponse = $this->kernel->handle($symfonyRequest, $this->type, $this->catch);

        return $this->psrHttpFactory->createResponse($symfonyResponse);
    }
}