<?php

declare(strict_types=1);


namespace Profesia\Symfony\Psr15Bundle\Tests\Acceptance;

use PHPUnit\Framework\TestCase;
use Profesia\Symfony\Psr15Bundle\Tests\Acceptance\Assets\TestKernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Filesystem\Filesystem;

class MainTest extends TestCase
{
    protected function tearDown(): void
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists(__DIR__ . '/Assets/cache')) {
            $fileSystem->remove(__DIR__ . '/Assets/cache');
        }
    }

    public function provideDataForIntegrationTest()
    {
        return [
            [
                ['GET'],
                '/1',
                '2 1 1 2 3 3 1',
            ],
            [
                ['GET'],
                '/2',
                '1 2 3',
            ],
            [
                ['GET', 'POST', 'PUT', 'DELETE'],
                '/3',
                '1 1 1 2 3',
            ],
            [
                ['DELETE'],
                '/2',
                '1 2 3 3 1 3'
            ],
            [
                ['POST'],
                '/2',
                '1 2 3 1 2 3 3 1 3'
            ]
        ];
    }

    /**
     * @param string[] $methods
     * @param string   $route
     * @param          $toCompare
     *
     * @dataProvider provideDataForIntegrationTest
     */
    public function testIntegration(array $methods, string $route, $toCompare)
    {
        $kernel = new TestKernel('test', false);
        $kernel->boot();

        $client = new KernelBrowser(
            $kernel
        );
        $client->catchExceptions(false);

        foreach ($methods as $method) {
            $client->request($method, $route);
            $response = $client->getResponse();

            $this->assertEquals(200, $response->getStatusCode());
            $content = json_decode($response->getContent(), true);
            $this->assertEquals($toCompare, $content['headers']);
        }
    }
}
