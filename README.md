Blast\ReflectionFactory
=======================

[![Build Status](https://travis-ci.org/mtymek/blast-reflection-factory.svg?branch=master)](https://travis-ci.org/mtymek/blast-reflection-factory)

Universal auto-wiring factory for Zend ServiceManager.

## Introduction

Writing factories for Zend ServiceManager can be boring, repeatable task. Typical service
will consume one or more dependencies using constructor injection: 

```php
class Mailer
{
    public function __construct(MailTransportInterface $transport, MailRenderer $renderer)
    {
      // ...
    }
}
```

This is how factory is going to look like:

```php
class MailerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new Mailer(
            $container->get(MailTransportInterface::class),
            $container->get(MailRenderer::class)
        );
    }
}
```

In typical application, you will end up with multiple factories that simply pull some 
services and create new object. `ReflectionFactory` can take care of this use case 
for you - it uses `Reflection` to scan parameter types in constructor and instantiates
new object based on this information.

## Installation

Install this package using Composer:

```
$ composer require mtymek/blast-reflection-factory
```

## Usage

After installing this package, all you have to do is to tell ServiceManager
to use `ReflectionFactory` to create your services.

For Zend Expressive application, configuration can look like this:

```php
use Blast\ReflectionFactory\ReflectionFactory;

return [
    'dependencies' => [
        'factories' => [
            // use normal factory for classes that require complex instantiation 
            SmtpMailTransport::class => SmtpMailTransportFactory::class,
             
            // use ReflectionFactory for auto-wiring auto-wire 
            MailRenderer::class => ReflectionFactory::class,
            Mailer::class => ReflectionFactory::class,
        ],
        'aliases' => [
            MailTransportInterface::class => SmtpMailTransport::class,
        ],
    ]
];
```

### Caching

Auto-wiring is expensive operation, so `ReflectionFactory` allows to store the result
on disk to be reused later: 

```php
\Blast\ReflectionFactory\ReflectionFactory::enableCache('data/cache/reflection-factory.cache.php');
```

If you are using Zend Expressive Skeleton Application, then `config/container.php` would
be a good place to enable this cache.

#### Warming-up cache

Cache file is automatically updated when a service is pulled from the container for the first 
time. This can lead to race conditions when your application is under heavy load. In order to
avoid it, cache should be warmed up during deployment phase.
Easiest way to do it is to go through all configured factories, pulling every serice from
the container.

Example script for applications based on Zend Expressive Skeleton:
```
<?php
// warmup-reflection-factory-cache.php

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

/** @var \Interop\Container\ContainerInterface $container */
$container = require 'config/container.php';

$config = require 'config/config.php';
foreach ($config['dependencies']['factories'] as $type => $factory) {
    $container->get($type);
}
```

## Limitations

`ReflectionFactory` is only meant to be used in typical scenario, when all dependencies
are injected using constructor. All of them must be type-hinted - otherwise `ReflectionFactory`
won't be able to resolve them.
Despite this limitation, this library should still let you reduce number of factories you
have to write.

What is not supported (and won't be):
* scalar value injection
* setter injection
