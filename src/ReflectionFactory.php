<?php

namespace Blast\ReflectionFactory;

use Interop\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class ReflectionFactory
{
    /** @var array */
    private static $parameterCache = [];

    /** @var string */
    private static $cacheFile = null;

    public function __invoke(ContainerInterface $container, $requestedName, $requestedNameV2 = null)
    {
        // SMv2?
        if (!method_exists($container, 'configure')) {
            $requestedName = $requestedNameV2;
        }

        $parameterTypes = $this->getContructorParameters($container, $requestedName);

        $parameters = [];
        foreach ($parameterTypes as $type) {
            $parameters[] = $container->get($type);
        }

        return new $requestedName(...$parameters);
    }

    /**
     * @param string $cacheFile
     */
    public static function enableCache($cacheFile)
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

    private function getContructorParameters(ContainerInterface $container, $requestedName)
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

    private function reflectConstructorParams(ContainerInterface $container, $requestedName)
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

    private function resolveParameterType(ContainerInterface $container, $requestedName)
    {
        /**
         * @param ReflectionParameter $parameter
         * @return mixed
         * @throws ServiceNotFoundException If type-hinted parameter cannot be
         *   resolved to a service in the container.
         */
        return function (ReflectionParameter $parameter) use ($container, $requestedName) {
            if (! $parameter->getClass()) {
                throw new ServiceNotFoundException(sprintf(
                    'Cannot create "%s"; parameter "%s" has no type hint.',
                    $requestedName,
                    $parameter->getName()
                ));
            }
            $type = $parameter->getClass()->getName();

            if (! $container->has($type)) {
                throw new ServiceNotFoundException(sprintf(
                    'Cannot create "%s"; unable to resolve parameter "%s" using type hint "%s"',
                    $requestedName,
                    $parameter->getName(),
                    $type
                ));
            }
            return $type;
        };
    }
}
