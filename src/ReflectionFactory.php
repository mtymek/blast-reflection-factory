<?php

namespace Blast\ReflectionFactory;

use Interop\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class ReflectionFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $reflectionClass = new ReflectionClass($requestedName);
        if (null === ($constructor = $reflectionClass->getConstructor())) {
            return new $requestedName();
        }
        $reflectionParameters = $constructor->getParameters();
        if (empty($reflectionParameters)) {
            return new $requestedName();
        }
        $parameters = array_map(
            $this->resolveParameter($container, $requestedName),
            $reflectionParameters
        );
        return new $requestedName(...$parameters);
    }

    /**
     * Resolve a parameter to a value.
     *
     * Returns a callback for resolving a parameter to a value.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return callable
     */
    private function resolveParameter(ContainerInterface $container, $requestedName)
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
            return $container->get($type);
        };
    }
}
