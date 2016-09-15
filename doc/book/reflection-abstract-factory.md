# Reflection Factory

- Since 3.2.0.

Writing a factory class for each and every service that has dependencies
can be tedious, particularly in early development as you are still sorting
out dependencies.

zend-servicemanager ships with `Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory`,
which provides a reflection-based approach to instantiation, resolving
constructor dependencies to the relevant services. The factory may be used as
either an abstract factory, or mapped to specific service names as a factory:

```php
use Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

return [
    /* ... */
    'service_manager' => [
        'abstract_factories' => [
            ReflectionBasedAbstractFactory::class,
        ],
        'factories' => [
            'MyModule\Model\FooModel' => ReflectionBasedAbstractFactory::class,
        ],
    ],
    /* ... */
];
```

Mapping services to the factory is more explicit and performant.

The factory operates with the following constraints/features:

- A parameter named `$config` typehinted as an array will receive the
  application "config" service (i.e., the merged configuration).
- Parameters typehinted against array, but not named `$config`, will
  be injected with an empty array.
- Scalar parameters will result in the factory raising an exception,
  unless a default value is present; if it is, that value will be used.
- If a service cannot be found for a given typehint, the factory will
  raise an exception detailing this.

`$options` passed to the factory are ignored in all cases, as we cannot
make assumptions about which argument(s) they might replace.

Once your dependencies have stabilized, we recommend writing a dedicated
factory, as reflection can introduce performance overhead; you may use the
[generate-factory-for-class console tool](console-tools.md#generate-factory-for-class)
to do so.

## Handling well-known services

Some services provided by Zend Framework components do not have
entries based on their class name (for historical reasons). As examples:

- `Zend\Console\Adapter\AdapterInterface` maps to the service name `ConsoleAdapter`,
- `Zend\Filter\FilterPluginManager` maps to the service name `FilterManager`,
- `Zend\Hydrator\HydratorPluginManager` maps to the service name `HydratorManager`,
- `Zend\InputFilter\InputFilterPluginManager` maps to the service name `InputFilterManager`,
- `Zend\Log\FilterPluginManager` maps to the service name `LogFilterManager`,
- `Zend\Log\FormatterPluginManager` maps to the service name `LogFormatterManager`,
- `Zend\Log\ProcessorPluginManager` maps to the service name `LogProcessorManager`,
- `Zend\Log\WriterPluginManager` maps to the service name `LogWriterManager`,
- `Zend\Serializer\AdapterPluginManager` maps to the service name `SerializerAdapterManager`,
- `Zend\Validator\ValidatorPluginManager` maps to the service name `ValidatorManager`,

To allow the `ReflectionBasedAbstractFactory` to find these, you have two
options.

The first is to pass an array of mappings via the constructor:

```php
$reflectionFactory = new ReflectionBasedAbstractFactory([
    \Zend\Console\Adapter\AdapterInterface::class     => 'ConsoleAdapter',
    \Zend\Filter\FilterPluginManager::class           => 'FilterManager',
    \Zend\Hydrator\HydratorPluginManager::class       => 'HydratorManager',
    \Zend\InputFilter\InputFilterPluginManager::class => 'InputFilterManager',
    \Zend\Log\FilterPluginManager::class              => 'LogFilterManager',
    \Zend\Log\FormatterPluginManager::class           => 'LogFormatterManager',
    \Zend\Log\ProcessorPluginManager::class           => 'LogProcessorManager',
    \Zend\Log\WriterPluginManager::class              => 'LogWriterManager',
    \Zend\Serializer\AdapterPluginManager::class      => 'SerializerAdapterManager',
    \Zend\Validator\ValidatorPluginManager::class     => 'ValidatorManager',
]);
```

This can be done either in your configuration file (which could be problematic
when considering serialization for caching), or during an early phase of
application bootstrapping.

For instance, with zend-mvc, this might be in your `Application` module's
bootstrap listener:

```php
namespace Application

use Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

class Module
{
    public function onBootstrap($e)
    {
        $application = $e->getApplication();
        $container = $e->getServiceManager();

        $container->addAbstractFactory(new ReflectionBasedAbstractFactory([
            /* ... */
        ]));
    }
}
```

For Expressive, it could be part of your `config/container.php` definition:

```php
$container = new ServiceManager();
(new Config($config['dependencies']))->configureServiceManager($container);
// Add the following:
$container->addAbstractFactory(new ReflectionBasedAbstractFactory([
    /* ... */
]));
```

The second approach is to extend the class, and define the map in the
`$aliases` property:

```php
namespace Application;

use Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

class ReflectionAbstractFactory extends ReflectionBasedAbstractFactory
{
    protected $aliases = [
        \Zend\Console\Adapter\AdapterInterface::class     => 'ConsoleAdapter',
        \Zend\Filter\FilterPluginManager::class           => 'FilterManager',
        \Zend\Hydrator\HydratorPluginManager::class       => 'HydratorManager',
        \Zend\InputFilter\InputFilterPluginManager::class => 'InputFilterManager',
        \Zend\Log\FilterPluginManager::class              => 'LogFilterManager',
        \Zend\Log\FormatterPluginManager::class           => 'LogFormatterManager',
        \Zend\Log\ProcessorPluginManager::class           => 'LogProcessorManager',
        \Zend\Log\WriterPluginManager::class              => 'LogWriterManager',
        \Zend\Serializer\AdapterPluginManager::class      => 'SerializerAdapterManager',
        \Zend\Validator\ValidatorPluginManager::class     => 'ValidatorManager',
    ];
}
```

You could then register it via class name in your service configuration.

## Alternatives

You may also use the [Config Abstract Factory](config-abstract-factory.md),
which gives slightly more flexibility in terms of mapping dependencies:

- If you wanted to map to a specific implementation, choose the
  `ConfigAbstractFactory`.
- If you need to map to a service that will return a scalar or array (e.g., a
  subset of the `'config'` service), choose the `ConfigAbstractFactory`.
- If you need a faster factory for production, choose the
  `ConfigAbstractFactory` or create a custom factory.

## References

This feature was inspired by [a blog post by Alexandre Lemaire](http://circlical.com/blog/2016/3/9/preparing-for-zend-f).
