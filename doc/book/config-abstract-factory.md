# Config Abstract Factory

You can simplify the process of creating factories by adding the 
`ConfigAbstractFactory` to your service manager. This allows you to define
services using a configuration map, rather than having to create separate 
factories for all your services. 

## Enabling
You can enable the `ConfigAbstractFactory` in the same way that you would enable 
any other abstract factory - in your own code:

```php
$serviceManager = new ServiceManager();
$serviceManager->addAbstractFactory(new ConfigAbstractFactory());
```

Or within any config provider using:

```php
return [
    'service_manager' => [
        'abstract_factories' => [
            ConfigAbstractFactories::class,
        ],
    ],
];
```

## Configuring

Configuration is done through the `config` service manager key, in an array with
the key `Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory`. If you are using 
config merging from the MVC/ModuleManager, in this just means that you can 
add a `ConfigAbstractFactory::class` key to your merged config which contains service
definitions, where the key is the service name (typically the FQNS of the class you are 
defining), and the value is an array of it's dependencies, also defined as container keys.

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

The definition tells the service manager how this abstract factory should manage dependencies in
the classes defined. In the above example, `MySimpleClass` has a single dependency on a `Logger`
instance. The abstract factory will simply look to fulfil that dependency by calling a `get` 
call with that key on the service manager it is attached to. In this way, you can create the 
correct tree of dependencies to successfully return any given service. Note that `Handler` does not have a 
configuration for the abstract factory, but this would work if `Handler` had a traditional factory and 
can be created by this service manager.

For a better example, consider the following classes:

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
