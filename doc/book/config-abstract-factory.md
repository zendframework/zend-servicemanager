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
definitions:

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
