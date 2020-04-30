<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\HttpKernel;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\RequestHandler\SymfonyKernelRequestHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\RebootableInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

class Psr15HttpKernelDecorator implements KernelInterface, RebootableInterface, TerminableInterface
{
    /** @var KernelInterface|RebootableInterface|TerminableInterface */
    private $decoratedKernel;

    /** @var HttpMessageFactoryInterface */
    private $psrHttpFactory;

    /** @var HttpFoundationFactory */
    private $foundationHttpFactory;

    private function __construct(
        KernelInterface $decoratedKernel,
        HttpMessageFactoryInterface $psrHttpFactory,
        HttpFoundationFactoryInterface $foundationHttpFactory
    ) {
        $this->decoratedKernel       = $decoratedKernel;
        $this->psrHttpFactory        = $psrHttpFactory;
        $this->foundationHttpFactory = $foundationHttpFactory;
    }

    public static function createFromKernelWithDefaults(KernelInterface $kernel): self
    {
        $psr17Factory = new Psr17Factory();

        return new self(
            $kernel,
            new PsrHttpFactory(
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
                $psr17Factory
            ),
            new HttpFoundationFactory()
        );
    }

    public function registerBundles()
    {
        return $this->decoratedKernel->registerBundles();
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $this->decoratedKernel->registerContainerConfiguration($loader);
    }

    public function boot()
    {
        $this->decoratedKernel->boot();
    }

    public function shutdown()
    {
        $this->decoratedKernel->shutdown();
    }

    public function getBundles()
    {
        return $this->decoratedKernel->getBundles();
    }

    public function getBundle(string $name)
    {
        return $this->decoratedKernel->getBundle($name);
    }

    public function locateResource(string $name)
    {
        return $this->decoratedKernel->locateResource($name);
    }

    public function getEnvironment()
    {
        return $this->decoratedKernel->getEnvironment();
    }

    public function isDebug()
    {
        return $this->decoratedKernel->isDebug();
    }

    public function getProjectDir()
    {
        return $this->decoratedKernel->getProjectDir();
    }

    public function getContainer()
    {
        return $this->decoratedKernel->getContainer();
    }

    public function getStartTime()
    {
        return $this->decoratedKernel->getStartTime();
    }

    public function getCacheDir()
    {
        return $this->decoratedKernel->getCacheDir();
    }

    public function getLogDir()
    {
        return $this->decoratedKernel->getLogDir();
    }

    public function getCharset()
    {
        return $this->decoratedKernel->getCharset();
    }

    public function handle(Request $request, int $type = self::MASTER_REQUEST, bool $catch = true)
    {
        $handler = SymfonyKernelRequestHandler::createFromObjects(
            $this->foundationHttpFactory,
            $this->psrHttpFactory,
            $this->decoratedKernel,
            $type,
            $catch
        );

        $psrRequest  = $this->psrHttpFactory->createRequest($request);
        $psrResponse = $handler->handle($psrRequest);

        return $this->foundationHttpFactory->createResponse($psrResponse);
    }

    public function reboot(?string $warmupDir)
    {
        return $this->decoratedKernel->reboot($warmupDir);
    }

    public function terminate(Request $request, Response $response)
    {
        $this->decoratedKernel->terminate($request, $response);
    }
}