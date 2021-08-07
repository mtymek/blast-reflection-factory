<?php

declare(strict_types=1);

namespace Blast\Test\ReflectionFactory;

use Blast\ReflectionFactory\ReflectionFactory;
use Blast\Test\ReflectionFactory\Asset\BarService;
use Blast\Test\ReflectionFactory\Asset\FooService;
use Blast\Test\ReflectionFactory\Asset\MissingTypeHint;
use Blast\Test\ReflectionFactory\Asset\QuxService;
use PHPUnit\Framework\TestCase;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;

class ReflectionFactoryTest extends TestCase
{
    public function testCreatesService()
    {
        $config = new Config(
            [
                'factories' => [
                    FooService::class => ReflectionFactory::class,
                    BarService::class => ReflectionFactory::class,
                    QuxService::class => ReflectionFactory::class,
                ]
            ]
        );
        $container = new ServiceManager();
        $config->configureServiceManager($container);

        $barSevice = $container->get(BarService::class);
        $this->assertInstanceOf(BarService::class, $barSevice);
    }

    public function testCreatesServiceFromCachedDefinition()
    {
        ReflectionFactory::enableCache(__DIR__ . '/Asset/reflection-factory.cache.php');
        $config = new Config(
            [
                'factories' => [
                    FooService::class => ReflectionFactory::class,
                    BarService::class => ReflectionFactory::class,
                    QuxService::class => ReflectionFactory::class,
                ]
            ]
        );
        $container = new ServiceManager();
        $config->configureServiceManager($container);

        $barSevice = $container->get(BarService::class);
        $this->assertInstanceOf(BarService::class, $barSevice);
    }

    public function testGeneratesNewCache()
    {
        $cacheFile = tempnam(sys_get_temp_dir(), 'blast');
        ReflectionFactory::enableCache($cacheFile);
        $config = new Config(
            [
                'factories' => [
                    FooService::class => ReflectionFactory::class,
                    BarService::class => ReflectionFactory::class,
                    QuxService::class => ReflectionFactory::class,
                ]
            ]
        );
        $container = new ServiceManager();
        $config->configureServiceManager($container);

        $barSevice = $container->get(BarService::class);
        $this->assertInstanceOf(BarService::class, $barSevice);
        $cachedDefinitions = include $cacheFile;
        $this->assertEquals(
            [
                FooService::class,
                QuxService::class,
            ],
            $cachedDefinitions[BarService::class]
        );
        $this->assertEquals([], $cachedDefinitions[FooService::class]);
        $this->assertEquals([], $cachedDefinitions[QuxService::class]);

        unlink($cacheFile);
    }

    public function testCannotCreateServiceWithoutConstructorTypeHints()
    {
        $config = new Config(
            [
                'factories' => [
                    FooService::class => ReflectionFactory::class,
                    MissingTypeHint::class => ReflectionFactory::class,
                ]
            ]
        );
        $container = new ServiceManager();
        $config->configureServiceManager($container);

        $this->expectException(ServiceNotFoundException::class);
        $container->get(MissingTypeHint::class);
    }

    public function testCannotCreateServiceWithMissingDependency()
    {
        $config = new Config(
            [
                'factories' => [
                    BarService::class => ReflectionFactory::class,
                    QuxService::class => ReflectionFactory::class,
                ]
            ]
        );
        $container = new ServiceManager();
        $config->configureServiceManager($container);

        $this->expectException(ServiceNotFoundException::class);
        $container->get(BarService::class);
    }
}
