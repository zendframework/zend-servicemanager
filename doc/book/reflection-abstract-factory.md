# Automating Factories

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
- Scalar parameters will be resolved as null values.
- If a service cannot be found for a given typehint, the factory will
  raise an exception detailing this.
- Some services provided by Zend Framework components do not have
  entries based on their class name (for historical reasons); the
  factory contains a map of these class/interface names to the
  corresponding service name to allow them to resolve. You may override this
  list by providing an array of class name/service name pairs to the
  constructor; by default, the following are mapped:
    - `Zend\Console\Adapter\AdapterInterface` maps to `ConsoleAdapter`,
    - `Zend\Filter\FilterPluginManager` maps to `FilterManager`,
    - `Zend\Hydrator\HydratorPluginManager` maps to `HydratorManager`,
    - `Zend\InputFilter\InputFilterPluginManager` maps to `InputFilterManager`,
    - `Zend\Log\FilterPluginManager` maps to `LogFilterManager`,
    - `Zend\Log\FormatterPluginManager` maps to `LogFormatterManager`,
    - `Zend\Log\ProcessorPluginManager` maps to `LogProcessorManager`,
    - `Zend\Log\WriterPluginManager` maps to `LogWriterManager`,
    - `Zend\Serializer\AdapterPluginManager` maps to `SerializerAdapterManager`,
    - `Zend\Validator\ValidatorPluginManager` maps to `ValidatorManager`,

`$options` passed to the factory are ignored in all cases, as we cannot
make assumptions about which argument(s) they might replace.

Once your dependencies have stabilized, we recommend writing a dedicated
factory, as reflection can introduce performance overhead.

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
