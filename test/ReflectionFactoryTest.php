<?php

namespace Blast\Test\ReflectionFactory;

use Blast\ReflectionFactory\ReflectionFactory;
use Blast\Test\ReflectionFactory\Asset\BarService;
use Blast\Test\ReflectionFactory\Asset\FooService;
use Blast\Test\ReflectionFactory\Asset\MissingTypeHint;
use Blast\Test\ReflectionFactory\Asset\QuxService;
use PHPUnit_Framework_TestCase;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceManager;

class ReflectionFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreatesService()
    {
        $container = new ServiceManager(
            [
                'factories' => [
                    FooService::class => ReflectionFactory::class,
                    BarService::class => ReflectionFactory::class,
                    QuxService::class => ReflectionFactory::class,
                ]
            ]
        );

        $barSevice = $container->get(BarService::class);
        $this->assertInstanceOf(BarService::class, $barSevice);
    }

    public function testCreatesServiceFromCachedDefinition()
    {
        ReflectionFactory::enableCache(__DIR__ . '/Asset/reflection-factory.cache.php');
        $container = new ServiceManager(
            [
                'factories' => [
                    FooService::class => ReflectionFactory::class,
                    BarService::class => ReflectionFactory::class,
                    QuxService::class => ReflectionFactory::class,
                ]
            ]
        );

        $barSevice = $container->get(BarService::class);
        $this->assertInstanceOf(BarService::class, $barSevice);
    }

    public function testGeneratesNewCache()
    {
        $cacheFile = tempnam(sys_get_temp_dir(), 'blast');
        ReflectionFactory::enableCache($cacheFile);
        $container = new ServiceManager(
            [
                'factories' => [
                    FooService::class => ReflectionFactory::class,
                    BarService::class => ReflectionFactory::class,
                    QuxService::class => ReflectionFactory::class,
                ]
            ]
        );

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
        $container = new ServiceManager(
            [
                'factories' => [
                    FooService::class => ReflectionFactory::class,
                    MissingTypeHint::class => ReflectionFactory::class,
                ]
            ]
        );

        $this->expectException(ServiceNotFoundException::class);
        $container->get(MissingTypeHint::class);
    }

    public function testCannotCreateServiceWithMissingDependency()
    {
        $container = new ServiceManager(
            [
                'factories' => [
                    BarService::class => ReflectionFactory::class,
                    QuxService::class => ReflectionFactory::class,
                ]
            ]
        );

        $this->expectException(ServiceNotFoundException::class);
        $container->get(BarService::class);
    }
}
