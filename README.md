Blast Reflection Factory
========================

Universal factory for Zend ServiceManager.

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
for you. It uses `Reflection` to scan parameter types in constructor and constructs
new object based on this information.

## Usage

Use `ReflectionFactory` 

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
``

If you are using Zend Expressive Skeleton Application, then `config/container.php` would
be a good place to enable this cache.

## Limitations

`ReflectionFactory` is only meant to be used in typical scenario, when all dependencies
are injected using constructor. All of them must be type-hinted - otherwise `ReflectionFactory`
won't be able to resolve them.
