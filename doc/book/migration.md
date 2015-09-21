# Migration Guide

The Service Manager was first introduced for Zend Framework 2.0.0. Its API
remained the same throughout that version.

Version 3 is the first new major release of the Service Manager, and contains a
number of backwards compatibility breaks. These were introduced to provide
better performance and stability.

## Case Sensitivity and Normalization

v2 normalized service names as follows:

- It stripped non alphanumeric characters.
- It lowercased the resulting string.

This was done to help prevent typographical errors from creating configuration
errors. However, it also presented a large performance hit, and led to some
unexpected behaviors.

**In v3, service names are case sensitive, and are not normalized in any way.**

As such, you *must* refer to services using the same case in which they were
registered.

## Configuration

Configuration for v2 consisted of the following:

```php
[
    'services' => [
        // service name => instance pairs
    ],
    'aliases' => [
        // alias => service name pairs
    ],
    'invokables' => [
        // service name => class name pairs
    ],
    'factories' => [
        // service name => factory pairs
    ],
    'abstract_factories' => [
        // abstract factories
    ],
    'initializers' => [
        // initializers
    ],
    'delegators' => [
        // service name => [ delegator factories ]
    ],
    'shared' => [
        // service name => boolean
    ],
    'share_by_default' => boolean,
]
```

In v3, the configuration remains roughly the same, with the following changes:

```php
[
    'services' => [
        // service name => instance pairs
    ],
    'aliases' => [
        // alias => service name pairs
    ],
    'factories' => [
        // service name => factory pairs
    ],
    'abstract_factories' => [
        // abstract factories
    ],
    'initializers' => [
        // initializers
    ],
    'delegators' => [
        // service name => [ delegator factories ]
    ],
    'shared' => [
        // service name => boolean
    ],
    'lazy_services' => [
        // The class_map is required if using lazy services:
        'class_map' => [
            // service name => class name pairs
        ],
        // The following are optional:
        'proxies_namespace'  => 'Alternate namespace to use for generated proxy classes',
        'proxies_target_dir' => 'path in which to write generated proxy classes',
        'write_proxy_files'  => true, // boolean; false by default
    ],
    'share_by_default' => boolean,
]
```

The main changes are that invokables no longer exist, and that lazy service
configuration is now integrated.

### Invokables

*Invokables no longer exist.* As such, that key is no longer relevant. In each
case, if the service name is also the name of the class, you can use the
`InvokableFactory` and assign the service as a factory.

As an example, if you previously had the following configuration:

```php
return [
    'invokables' => [
        'MyClass' => 'MyClass',
    ],
];
```

You will now use the following:

```php
use Zend\ServiceManager\Factory\InvokableFactory;

return [
    'factories' => [
        'MyClass' => InvokableFactory::class,
    ],
];
```

What if you were using a service name that differed from the class name?

```php
return [
    'invokables' => [
        'MyClass' => 'AnotherClass',
    ],
];
```

In this case, you will create two separate entries: an invokable factory for the
actual class, and an alias to it:

```php
use Zend\ServiceManager\Factory\InvokableFactory;

return [
    'aliases' => [
        'MyClass' => 'AnotherClass',
    ],
    'factories' => [
        'AnotherClass' => InvokableFactory::class,
    ],
];
```

Alternately, you can create a dedicated factory for `MyClass` that instantiates
the correct class.

### Lazy Services

In v2, if you wanted to create a lazy service, you needed to take the following
steps:

- Ensure you have a `config` service, with a `lazy_services` key that contained
  the configuration necessary for the `LazyServiceFactory`.
- Assign the `LazyServiceFactoryFactory` as a factory for the
  `LazyServiceFactory`
- Assign the `LazyServiceFactory` as a delegator factory for your service.

As an example:

```php
use Zend\ServiceManager\Proxy\LazyServiceFactoryFactory;

$config = [
    'lazy_services' => [
        'class_map' => [
            'MyClass' => 'MyClass',
        ],
        'proxies_namespace'  => 'TestAssetProxy',
        'proxies_target_dir' => 'data/proxies/',
        'write_proxy_files'  => true,
    ],
];

return [
    'services' => [
        'config' => $config,
    ],
    'invokables' => [
        'MyClass' => 'MyClass',
    ],
    'factories' => [
        'LazyServiceFactory' => LazyServiceFactoryFactory::class,
    ],
    'delegators' => [
        'MyClass' => [
            'LazyServiceFactory',
        ],
    ],
];
```

This was done in part because lazy services were introduced later in the v2
cycle, and not fully integrated in order to retain the API.

In order to reduce the number of dependencies and steps necessary to configure
lazy services, the following changes were made for v3:

- Lazy service configuration can now be passed directly to the service manager;
  it is no longer dependent on a `config` service.
- The ServiceManager itself is now responsible for creating the
  `LazyServiceFactory` delegator factory, based on the configuration present.

The above example becomes the following in v3:

```php
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\Proxy\LazyServiceFactory;

return [
    'factories' => [
        'MyClass' => InvokableFactory::class,
    ],
    'delegators' => [
        'MyClass' => [
            LazyServiceFactory::class,
        ],
    ],
    'lazy_services' => [
        'class_map' => [
            'MyClass' => 'MyClass',
        ],
        'proxies_namespace'  => 'TestAssetProxy',
        'proxies_target_dir' => 'data/proxies/',
        'write_proxy_files'  => true,
    ],
];
```

## ServiceLocatorInterface Changes

The `ServiceLocatorInterface` now extends the
[container-interop](https://github.com/container-interop/container-interop)
interface `ContainerInterface`, which defines the same `get()` and `has()`
methods as were previously defined.

Additionally, it adds a new method:

```php
public function build($name, array $options = null)
```

This method is defined to *always* return a *new* instance of the requested
service, and to allow using the provided `$options` when creating the instance.

## ServiceManager API Changes

`Zend\ServiceManager\ServiceManager` remains the primary interface with which
developers will interact. It has the following changes in v3:

- It is now immutable.
  - It accepts all configuration at instantiation.
  - It removes all methods that would alter state.
  - It adds a new method, `withConfig()`, for generating a new instance that
    merges the provided configuration with previous definitions.
- Peering capabilities were removed.
- Exceptions are *always* thrown when service instance creation fails or
  produces an error; you can no longer disable this.
- Configuration no longer requires a `Zend\ServiceManager\Config` instance; that
  class has been removed.
- It adds a new method, `build()`, for creating discrete service instances.

### Methods Removed

*The following methods are removed* in v3:

- `setAllowOverride()`/`getAllowOverride()`; since instances are now immutable,
  these no longer had any meaning.
- `setShareByDefault()`/`shareByDefault()`; this can be passed during
  instantiation or via `withConfig()`.
- `setThrowExceptionInCreate()`/`getThrowExceptionInCreate()`; exceptions are
  *always* thrown when errors are encountered during service instance creation.
- `setRetrieveFromPeeringManagerFirst()`/`retrieveFromPeeringManagerFirst()`;
  peering is no longer supported.
- `setInvokableClass()`; invokable classes are no longer supported separately,
  regardless.
- `setFactory()`; pass factories during instantiation or via `withConfig()`.
- `addAbstractFactory()`; provide abstract factories during instantiation or via
  `withConfig()`.
- `addDelegator()`; provide delegator factories during instantiation or via
  `withConfig()`.
- `addInitializer()`; pass initializers during instantiation or via
  `withConfig()`.
- `setService()`; provide concrete service instances during instantiation or via
  `withConfig()`.
- `setShared()`/`isShared()`; provide per-service sharing status at
  instantiation or via `withConfig()`.

### Constructor

The constructor now accepts an array of service configuration, not a
`Zend\ServiceManager\Config` instance.

### Immutability

The Service Manager is now immutable. This allows us to perform aggressive
caching, and prevents the need to check for state changes when new services are
added.

*Typically, you should pass all service configuration at instantiation.*

If you need to change a Service Manager instance — for instance, to add more
factories, delegators, etc. — the class provides a new method, `withConfig()`.
This method will merge the configuration you provide with that found in the
Service Manager instance in order to return a *new instance*:

```php
$updated = $container->withConfig([
    'factories' => [
        'MyClass' => InvokableFactory::class,
    ],
]);
```

### Use `build()` for discrete instances

The new method `build()` acts as a factory method for configured services, and
will *always* return a new instance, never a shared one.

Additionally, it provides factory capabilities; you may pass an additional,
optional argument, `$options`, which should be an array of additional options a
factory may use to create a new instance. This is primarily of interest when
creating plugin managers (more on plugin managers below), which may pass that
information on in order to create discrete plugin instances with specific state.

As examples:

```php
use Zend\Validator\Between;

$between = $container->build(Between::class, [
    'min'        => 5,
    'max'        => 10,
    'inclusive' => true,
]);

$alsoBetween = $container->build(Between::class, [
    'min'       => 0,
    'max'       => 100,
    'inclusive' => false,
]);
```

The above two validators would be different instances, with their own
configuration.

### has() no longer checks abstract factories by default

In v2, `has()` would also check abstract factories to see if any would match the
service. Depending on the number of abstract factories present, this can be an
expensive operation. As a result, in v3, we no longer check abstract factories
*by default*.

However, you *can* tell the Service Manager to check them by passing an optional
second argument to the method; a boolean is expected, and a `true` value
indicates that abstract factories should be checked:

```php
$container = new ServiceManager([
    'factories' => [
        'MyClass' => 'MyClass',
    ],
    'abstract_factories' => [
        'AbstractFactoryThatAlwaysResolves',
    ],
]);
```

Assuming that `AbstractFactoryThatAlwaysResolves` will resolve any service
(don't ever do this!), the following behavior is expected:

```php
$has = $container->has('MyClass');            // always true; factory is defined
                                              // for the service.
$has = $container->has('AnotherClass');       // false; no factory is defined
                                              // for the service, and not
                                              // looking in abstract factories.
$has = $container->has('AnotherClass', true); // true; no factory is defined for
                                              // the service, but we indicated
                                              // we'd look in abstract factories.
```

## Factories

All factory interfaces were moved to a `Factory` subnamespace. Additionally, the
signatures for all factories have changed.

### Removed and Renamed Factory Interfaces

- `Zend\ServiceManager\AbstractFactoryInterface` was *renamed* to
  `Zend\ServiceManager\Factory\AbstractFactoryInterface`.
- `Zend\ServiceManager\DelegatorFactoryInterface` was *renamed* to
  `Zend\ServiceManager\Factory\DelegatorFactoryInterface`.
- `Zend\ServiceManager\FactoryInterface` was *renamed* to
  `Zend\ServiceManager\Factory\FactoryInterface`.

### AbstractFactoryInterface

The previous signature of the `AbstractFactoryInterface` was:

```php
interface AbstractFactoryInterface
{
    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName);

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName);
}
```

The new signature is:

```php
interface AbstractFactoryInterface extends FactoryInterface
{
    /**
     * Can we create an instance for the given service name?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ContainerInterface $container, $requestedName);
}
```

Note that it now *extends* the `FactoryInterface` (detailed below), and thus the
factory logic has the same signature. Additionally, note that the
`canCreateServiceWithName()` now receives only two arguments, the container and
the requested service name.

### DelegatorFactoryInterface

The previous signature of the `DelegatorFactoryInterface` was:

```php
interface DelegatorFactoryInterface
{
    /**
     * A factory that creates delegates of a given service
     *
     * @param ServiceLocatorInterface $serviceLocator the service locator which requested the service
     * @param string                  $name           the normalized service name
     * @param string                  $requestedName  the requested service name
     * @param callable                $callback       the callback that is responsible for creating the service
     *
     * @return mixed
     */
    public function createDelegatorWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName, $callback);
}
```

The new signature is:

```php
interface DelegatorFactoryInterface
{
    /**
     * A factory that creates delegates of a given service
     *
     * @param  ContainerInterface $container
     * @param  string             $name
     * @param  callable           $callback
     * @param  null|array         $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null);
}
```

Note that the `$name` and `$requestedName` arguments are now merged into a
single `$name` argument, and that the factory now allows passing additional
options to use (typically as passed via `build()`).

### FactoryInterface

The previous signature of the `FactoryInterface` was:

```php
interface FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator);
}
```

The new signature is:

```php
interface FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null);
}
```

Note that the factory now accepts an additional *required* argument,
`$requestedName`; v2 already passed this argument, but it was not specified in
the interface itself. Additionally, a third *optional* argument, `$options`,
allows you to provide `$options` to the `ServiceManager::build()` method;
factories can then take these into account when creating an instance.

Because factories now can expect to receive the service name, they may be
re-used for multiple services, largely replacing abstract factories in version
3.

### New InvokableFactory Class

`Zend\ServiceManager\Factory\InvokableFactory` is a new `FactoryInterface`
implementation that provides the capabilities of the "invokable classes" present
in version 2. It essentially instantiates and returns the requested class name;
if `$options` is non-empty, it passes them directly to the constructor.

## Initializers

Initializers are still present in the Service Manager component, but exist
primarily for backwards compatibility; we recommend using delegator factories
for setter and interface injection instead of initializers, as those will be run
per-service, versus for all services.

The following changes were made to initializers:

- `Zend\ServiceManager\InitializerInterface` was renamed to
  `Zend\ServiceManager\Initializer\InitializerInterface`.
- The interface itself has a new signature.

The previous signature was:

```php
public function initialize($instance, ServiceLocatorInterface $serviceLocator)
```

It is now:

```php
public function __invoke(ContainerInterface $container, $instance)
```

The changes were made to ensure the signature is internally consistent with the
various factories.

## Plugin Managers

In version 2, plugin managers were `ServiceManager` instances that implemented
both the `MutableCreationOptionsInterface` and `ServiceLocatorAwareInterface`,
and extended `AbstractPluginManager`.  Plugin managers passed themselves to
factories, abstract factories, etc., requiring pulling the parent service
manager, if composed, in order to resolve application-level dependencies.

In version 3, we define the following:

- `Zend\ServiceManager\PluginManagerInterface`, which provides the public API
  differences from the `ServiceLocatorInterface`.
- `Zend\ServiceManager\AbstractPluginManager`, which gives the basic
  capabilities for plugin managers. The class now has a *required* dependency on
  the application-level service manager instance, which is passed to all
  factories, abstract factories, etc.

### PluginManagerInterface

`Zend\ServiceManager\PluginInterface` is a new interface for version 3,
extending `ServiceLocatorInterface` and adding one method:

```php
/**
 * Validate an instance
 *
 * @param  object $instance
 * @return void
 * @throws InvalidServiceException If created instance does not respect the
 *     constraint on type imposed by the plugin manager
 */
public function validate($instance);
```

All plugin managers *must* implement this interface.

### AbstractPluginManager

As it did in version 2, `AbstractPluginManager` extends `ServiceManager`. **That
means that all changes made to the `ServiceManager` for v3 also apply to the
`AbstractPluginManager`.**

In addition, the following changes are also true for v3:

- The constructor now accepts the following arguments, in the following order:
  - The parent container instance; this is usually the application-level
    `ServiceManager` instance.
  - Optionally, an array of configuration for the plugin manager instance; this
    should have the same format as for a `ServiceManager` instance.
- `validatePlugin()` was renamed to `validate()` (now defined in
  `PluginManagerInterface`). The `AbstractPluginManager` provides
  a basic implementation (detailed below).
- The signature of `get()` changes (more below).

`validate()` is defined as the following:

```php
public function validate($instance)
{
    if (empty($this->instanceOf) || $instance instanceof $this->instanceOf) {
        return;
    }

    throw new InvalidServiceException(sprintf(
        'Plugin manager "%s" expected an instance of type "%s", but "%s" was received',
        __CLASS__,
        $this->instanceOf,
        is_object($instance) ? get_class($instance) : gettype($instance)
    ));
}
```

Most plugin manager instances can therefore define the `$instanceOf` property to
indicate what plugin interface is considered valid for the plugin manager, and
make no further changes to the abstract plugin manager:

```php
protected $instanceOf = ValidatorInterface::class;
```

The `get()` signature changes from:

```php
public function get($name, $options = [], $usePeeringServiceManagers = true)
```

to:

```php
public function get($name, array $options = null)
```

Essentially: `$options` now *must* be an array if passed, and peering is no
longer supported.

### Plugin Service Creation

The `get()` method has new behavior:

- When non-empty `$options` are passed, it *always* delegates to `build()`, and
  thus will *always* return a *new instance*. If you are using `$options`, the
  assumption is that you are using the plugin manager as a factory, and thus the
  instance should not be cached.
- Without `$options`, `get()` will cache by default (the default behavior of
  `ServiceManager`). To *never* cache instances, either set the
  `$sharedByDefault` class property to `false`, or pass a boolean `false` value
  via the `shared_by_default` configuration key.

## DI Namespace

**The `Zend\ServiceManager\Di` namespace has been removed.**

The `Zend\Di` component is not actively maintained, and has been largely
deprecated during the ZF2 lifecycle in favor of the Service Manager. Its usage
as an abstract factory is problematic and error prone when used in conjunction
with the Service Manager; as such, we've removed it for the initial v3 release.

We may re-introduce it via a separate component in the future.

## Miscellaneous Interfaces, Traits, and Classes

The following interfaces, traits, and classes were *removed*:

- `Zend\ServiceManager\Config`
- `Zend\ServiceManager\ConfigInterface`
- `Zend\ServiceManager\MutableCreationOptionsInterface`; this was previously
  used by the `AbstractPluginManager`, and is no longer required as we ship a
  separate `PluginManagerInterface`, and because the functionality is
  encompassed by the `build()` method.
- `Zend\ServiceManager\MutableCreationOptionsTrait`
- `Zend\ServiceManager\Proxy\LazyServiceFactoryFactory`; its capabilities were
  moved directly into the `ServiceManager`.
- `Zend\ServiceManager\ServiceLocatorAwareInterface`
- `Zend\ServiceManager\ServiceLocatorAwareTrait`
- `Zend\ServiceManager\ServiceManagerAwareInterface`

The `ServiceLocatorAware` and `ServiceManagerAware` interfaces and traits were
too often abused under v2, and represent the antithesis of the purpose of the
Service Manager component; dependencies should be directly injected, and the
container should never be composed by objects.

The following classes have changes:

- `Zend\ServiceManager\Proxy\LazyServiceFactory` is now marked `final`, and
   implements `Zend\ServiceManager\Proxy\DelegatorFactoryInterface`. Its
   dependencies and capabilities remain the same.
