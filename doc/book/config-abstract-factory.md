# Config Abstract Factory

- Since 3.2.0

You can simplify the process of creating factories by registering
`Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory` with your service
manager instance. This allows you to define services using a configuration map,
rather than having to create separate factories for each of your services. 

## Enabling the ConfigAbstractFactory

Enable the `ConfigAbstractFactory` in the same way that you would enable 
any other abstract factory.

Programmatically:

```php
$serviceManager = new ServiceManager();
$serviceManager->addAbstractFactory(new ConfigAbstractFactory());
```

Or within configuration:

```php
return [
    // zend-mvc:
    'service_manager' => [
        'abstract_factories' => [
            ConfigAbstractFactory::class,
        ],
    ],

    // zend-expressive or ConfigProvider consumers:
    'dependencies' => [
        'abstract_factories' => [
            ConfigAbstractFactory::class,
        ],
    ],
];
```

Like all abstract factories starting in version 3, you may also use the config
abstract factory as a mapped factory, registering it as a factory for a specific
class:

```php
return [
    'service_manager' => [
        'factories' => [
            SomeCustomClass::class => ConfigAbstractFactory::class,
        ],
    ],
];
```

## Configuration

Configuration should be provided via the `config` service, which should return
an array or `ArrayObject`. `ConfigAbstractFactory` looks for a top-level key in
this service named after itself (i.e., `Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory`)
that is an array value. Each item in the array:

- Should have a key representing the service name (typically the fully
  qualified class name)
- Should have a value that is an array of each dependency, ordered using the
  constructor argument order, and using service names registered with the
  container.

As an example:

```php
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [
    ConfigAbstractFactory::class => [
        MyInvokableClass::class => [],
        MySimpleClass::class => [
            Logger::class,          
        ],
        Logger::class => [
            Handler::class,
        ],
    ],
];
```

The definition tells the service manager how this abstract factory should manage
dependencies in the classes defined. In the above example, `MySimpleClass` has a
single dependency on a `Logger` instance. The abstract factory will simply look
to fulfil that dependency by calling `get()` with that key on the container
passed to it. In this way, you can create the correct tree of
dependencies to successfully return any given service.

In the above example, note that the abstract factory configuration does not
contain configuration for the `Handler` class. At first glance, this appears as
if it will fail; however, if `Handler` is configured directly with the container
already &mdash; for example, mapped to a custom factory &mdash; the service will
be created and used as a dependency.

As another, more complete example, consider the following classes:

```php
class UserMapper
{
    public function __construct(Adapter $db, Cache $cache) {}
}

class Adapter
{
    public function __construct(array $config) {}
}

class Cache
{
    public function __construct(CacheAdapter $cacheAdapter) {}
}

class CacheAdapter
{
}
```

In this case, we can define the configuration for these classes as follows:

```php
// config/autoload/dependencies.php or anywhere that gets merged into global config
return [
    ConfigAbstractFactory::class => [
        CacheAdapter::class => [], // no dependencies
        Cache::class => [
            CacheAdapter::class, // dependency on the CacheAdapter key defined above
        ],
        UserMapper::class => [
            Adapter::class, // will be called using normal factory defined below
            Cache::class, // defined above and will be created using this abstract factory
        ],
    ],
    'service_manager' => [
        'factories' => [
            Adapter::class => AdapterFactory::class, // normal factory not using above config
        ],
    ],    
],
```
