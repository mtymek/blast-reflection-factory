<?php

declare(strict_types=1);

namespace Blast\ReflectionFactory;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class ReflectionFactory
{
    private static array $parameterCache = [];

    private static ?string $cacheFile = null;

    public function __invoke(ContainerInterface $container, string $requestedName)
    {
        $parameterTypes = $this->getConstructorParameters($container, $requestedName);

        $parameters = [];
        foreach ($parameterTypes as $type) {
            $parameters[] = $container->get($type);
        }

        return new $requestedName(...$parameters);
    }

    public static function enableCache(string $cacheFile): void
    {
        self::$cacheFile = $cacheFile;
        self::$parameterCache = [];

        if (file_exists(self::$cacheFile)) {
            $cached = include self::$cacheFile;
            if (is_array($cached)) {
                self::$parameterCache = $cached;
            }
        }
    }

    private function getConstructorParameters(ContainerInterface $container, string $requestedName): array
    {
        if (isset(self::$parameterCache[$requestedName])) {
            return self::$parameterCache[$requestedName];
        }

        self::$parameterCache[$requestedName] = $this->reflectConstructorParams($container, $requestedName);
        if (null !== self::$cacheFile) {
            file_put_contents(self::$cacheFile, '<?php return ' . var_export(self::$parameterCache, true) . ";\n");
        }

        return self::$parameterCache[$requestedName];
    }

    private function reflectConstructorParams(ContainerInterface $container, string $requestedName): array
    {
        $reflectionClass = new ReflectionClass($requestedName);
        if (null === ($constructor = $reflectionClass->getConstructor())) {
            return [];
        }
        $reflectionParameters = $constructor->getParameters();
        if (empty($reflectionParameters)) {
            return [];
        }
        $parameters = array_map(
            $this->resolveParameterType($container, $requestedName),
            $reflectionParameters
        );
        return $parameters;
    }

    private function resolveParameterType(ContainerInterface $container, string $requestedName)
    {
        /**
         * @throws ServiceNotFoundException If type-hinted parameter cannot be
         *   resolved to a service in the container.
         */
        return function (ReflectionParameter $parameter) use ($container, $requestedName) {
            if (! $parameter->getType()) {
                throw new ServiceNotFoundException(sprintf(
                    'Cannot create "%s"; parameter "%s" has no type hint.',
                    $requestedName,
                    $parameter->getName()
                ));
            }

            $name = $parameter->getType() && !$parameter->getType()->isBuiltin()
               ? $parameter->getType()->getName()
                : null;

            if (! $name) {
                throw new ServiceNotFoundException(sprintf(
                    'Cannot create "%s"; parameter "%s" is a built-in type.',
                    $requestedName,
                    $parameter->getName()
                ));
            }

            if (! $container->has($name)) {
                throw new ServiceNotFoundException(sprintf(
                    'Cannot create "%s"; unable to resolve parameter "%s" using type hint "%s"',
                    $requestedName,
                    $parameter->getName(),
                    $name
                ));
            }
            return $name;
        };
    }
}
