<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;

class ConfigurationPathTest extends MockeryTestCase
{
    public function testCanInvalidateAnEmptyString()
    {
        $configurationHttpMethod = ConfigurationHttpMethod::createDefault();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Path should be a string composed at least of the '/' character");
        ConfigurationPath::createFromConfigurationHttpMethodAndString(
            $configurationHttpMethod,
            ''
        );
    }

    public function testCanInvalidNonPathString()
    {
        $configurationHttpMethod = ConfigurationHttpMethod::createDefault();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Path should be a string composed at least of the '/' character");
        ConfigurationPath::createFromConfigurationHttpMethodAndString(
            $configurationHttpMethod,
            'asdsa'
        );
    }

    public function testCanCreate()
    {
        $configurationHttpMethod = ConfigurationHttpMethod::createDefault();
        ConfigurationPath::createFromConfigurationHttpMethodAndString(
            $configurationHttpMethod,
            '/'
        );

        ConfigurationPath::createFromConfigurationHttpMethodAndString(
            $configurationHttpMethod,
            '/test'
        );
    }

    public function testCanExportConfiguration()
    {
        $configurationHttpMethod = ConfigurationHttpMethod::createDefault();
        $path                    = ConfigurationPath::createFromConfigurationHttpMethodAndString(
            $configurationHttpMethod,
            '/'
        );

        /** @var MockInterface|AbstractMiddlewareChainItem $middlewareChain */
        $middlewareChain = Mockery::mock(AbstractMiddlewareChainItem::class);

        $allMethods  = HttpMethod::getPossibleValues();
        $middlewares = [];
        foreach ($allMethods as $method) {
            $middlewares[$method] = $middlewareChain;
        }
        $returnValue = $path->exportConfigurationForMiddleware(
            $middlewares
        );

        $this->assertCount(1, $returnValue);
        $this->assertArrayHasKey('/', $returnValue[1]);
    }
}