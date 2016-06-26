<?php
/**
 * Warm-up cache for ReflectionFactory.
 *
 * This file iterates over all factories in SM configuration, pulling
 * all services from container. It will pre-fill cache file with
 * every definition that may be needed later.
 */

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

/** @var \Interop\Container\ContainerInterface $container */
$container = require 'config/container.php';

$config = require 'config/config.php';
foreach ($config['dependencies']['factories'] as $type => $factory) {
    $container->get($type);
}
