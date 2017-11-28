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

A number of changes have been made to configuration of service and plugin
managers:

- Minor changes in configuration arrays may impact your usage.
- `ConfigInterface` implementations and consumers will need updating.

### Configuration arrays

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

In v3, the configuration remains the same, with the following additions:

```php
[
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
]
```

The main change is the addition of integrated lazy service configuration is now
integrated.

### ConfigInterface

The principal change to the `ConfigInterface` is the addition of the
`toArray()` method. This method is intended to return a configuration array in
the format listed above, for passing to either the constructor or the
`configure()` method of the `ServiceManager`..

### Config class

`Zend\ServiceManager\Config` has been updated to follow the changes to the
`ConfigInterface` and `ServiceManager`. This essentially means that it removes
the various getter methods, and adds the `toArray()` method.

## Invokables

*Invokables no longer exist,* at least, not identically to how they existed in
ZF2.

Internally, `ServiceManager` now does the following for `invokables` entries:

- If the name and value match, it creates a `factories` entry mapping the
  service name to `Zend\ServiceManager\Factory\InvokableFactory`.
- If the name and value *do not* match, it creates an `aliases` entry mapping the
  service name to the class name, *and* a `factories` entry mapping the class
  name to `Zend\ServiceManager\Factory\InvokableFactory`.

This means that you can use your existing `invokables` configuration from
version 2 in version 3. However, we recommend starting to update your
configuration to remove `invokables` entries in favor of factories (and aliases,
if needed).

> #### Invokables and plugin managers
>
> If you are creating a plugin manager and in-lining invokables into the class
> definition, you will need to make some changes.
>
> `$invokableClasses` will need to become `$factories` entries, and you will
> potentially need to add `$aliases` entries.
>
> As an example, consider the following, from zend-math v2.x:
>
> ```php
> class AdapterPluginManager extends AbstractPluginManager
> {
>     protected $invokableClasses = [
>         'bcmath' => Adapter\Bcmath::class,
>         'gmp'    => Adapter\Gmp::class,
>     ];
> }
> ```
>
> Because we no longer define an `$invokableClasses` property, for v3.x, this
> now becomes:
>
> ```php
> use Zend\ServiceManager\Factory\InvokableFactory;
>
> class AdapterPluginManager extends AbstractPluginManager
> {
>     protected $aliases = [
>         'bcmath' => Adapter\Bcmath::class,
>         'gmp'    => Adapter\Gmp::class,
>     ];
>
>     protected $factories = [
>         Adapter\BcMath::class => InvokableFactory::class,
>         Adapter\Gmp::class    => InvokableFactory::class,
>     ];
> }
> ```

## Lazy Services

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

Additionally, assuming you have configured lazy services initially with the
proxy namespace, target directory, etc., you can map lazy services using the new
method `mapLazyService($name, $class)`:

```php
$container->mapLazyService('MyClass', 'MyClass');
// or, more simply:
$container->mapLazyService('MyClass');
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

- It adds a new method, `configure()`, which allows configuring all instance
  generation capabilities (aliases, factories, abstract factories, etc.) at
  once.
- Peering capabilities were removed.
- Exceptions are *always* thrown when service instance creation fails or
  produces an error; you can no longer disable this.
- Configuration no longer requires a `Zend\ServiceManager\Config` instance.
  `Config` can be used, but is not needed.
- It adds a new method, `build()`, for creating discrete service instances.

### Methods Removed

*The following methods are removed* in v3:

- `setShareByDefault()`/`shareByDefault()`; this can be passed during
  instantiation or via `configure()`.
- `setThrowExceptionInCreate()`/`getThrowExceptionInCreate()`; exceptions are
  *always* thrown when errors are encountered during service instance creation.
- `setRetrieveFromPeeringManagerFirst()`/`retrieveFromPeeringManagerFirst()`;
  peering is no longer supported.

### Constructor

The constructor now accepts an array of service configuration, not a
`Zend\ServiceManager\Config` instance.

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

## Factories

Internally, the `ServiceManager` now only uses the new factory interfaces
defined in the `Zend\ServiceManager\Factory` namespace. These *replace* the
interfaces defined in version 2, and define completely new signatures.

For migration purposes, all original interfaces were retained, and now inherit
from the new interfaces. This provides a migration path; you can add the methods
defined in the new interfaces to your existing factories targeting v2, and
safely upgrade. (Typically, you will then have the version 2 methods proxy to
those defined in version 3.)

### Interfaces and relations to version 2

| Version 2 Interface                                       | Version 3 Interface                                       |
| :-------------------------------------------------------: | :-------------------------------------------------------: |
| `Zend\ServiceManager\AbstractFactoryInterface`            | `Zend\ServiceManager\Factory\AbstractFactoryInterface`    |
| `Zend\ServiceManager\DelegatorFactoryInterface`           | `Zend\ServiceManager\Factory\DelegatorFactoryInterface`   |
| `Zend\ServiceManager\FactoryInterface`                    | `Zend\ServiceManager\Factory\FactoryInterface`            |

The version 2 interfaces now extend those in version 3, but are marked
**deprecated**. You can continue to use them, but will be required to update
your code to use the new interfaces in the future.

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
     * Does the factory have a way to create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName);
}
```

Note that it now *extends* the `FactoryInterface` (detailed below), and thus the
factory logic has the same signature.

In v2, the abstract factory defined the method `canCreateServiceWithName()`; in
v3, this is renamed to `canCreate()`, and the method also now receives only two
arguments, the container and the requested service name.

To prepare your version 2 implementation to work upon upgrade to version 3:

- Add the methods `canCreate()` and `__invoke()` as defined in version 3.
- Modify your existing `canCreateServiceWithName()` method to proxy to
  `canCreate()`
- Modify your existing `createServiceWithName()` method to proxy to
  `__invoke()`

As an example, given the following implementation from version 2:

```php
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LenientAbstractFactory implements AbstractFactoryInterface
{
    public function canCreateServiceWithName(ServiceLocatorInterface $services, $name, $requestedName)
    {
        return class_exists($requestedName);
    }

    public function createServiceWithName(ServiceLocatorInterface $services, $name, $requestedName)
    {
        return new $requestedName();
    }
}
```

To update this for version 3 compatibility, you will add the methods
`canCreate()` and `__invoke()`, move the code from the existing methods into
them, and update the existing methods to proxy to the new methods:

```php
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LenientAbstractFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return class_exists($requestedName);
    }

    public function canCreateServiceWithName(ServiceLocatorInterface $services, $name, $requestedName)
    {
        return $this->canCreate($services, $requestedName);
    }

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new $requestedName();
    }

    public function createServiceWithName(ServiceLocatorInterface $services, $name, $requestedName)
    {
        return $this($services, $requestedName);
    }
}
```

After you have upgraded to version 3, you can take the following steps to remove
the migration artifacts:

- Update your class to implement the new interface.
- Remove the `canCreateServiceWithName()` and `createServiceWithName()` methods
  from your implementation.

From our example above, we would update the class to read as follows:

```php
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\AbstractFactoryInterface; // <-- note the change!

class LenientAbstractFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return class_exists($requestedName);
    }

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new $requestedName();
    }
}
```

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

To prepare your existing delegator factories for version 3, take the following
steps:

- Implement the `__invoke()` method in your existing factory, copying the code
  from your existing `createDelegatorWithName()` method into it.
- Modify the `createDelegatorWithName()` method to proxy to the new method.

Consider the following delegator factory that works for version 2:

```php
use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ObserverAttachmentDelegator implements DelegatorFactoryInterface
{
    public function createDelegatorWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName, $callback)
    {
        $subject = $callback();
        $subject->attach($serviceLocator->get(Observer::class);
        return $subject;
    }
}
```

To prepare this for version 3, we'd implement the `__invoke()` signature from
version 3, and modify `createDelegatorWithName()` to proxy to it:

```php
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ObserverAttachmentDelegator implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, callable $callback, array $options = null)
    {
        $subject = $callback();
        $subject->attach($container->get(Observer::class);
        return $subject;
    }

    public function createDelegatorWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName, $callback)
    {
        return $this($serviceLocator, $requestedName, $callback);
    }
}
```

After you have upgraded to version 3, you can take the following steps to remove
the migration artifacts:

- Update your class to implement the new interface.
- Remove the `createDelegatorWithName()` method from your implementation.

From our example above, we would update the class to read as follows:

```php
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface; // <-- note the change!

class ObserverAttachmentDelegator implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, callable $callback, array $options = null)
    {
        $subject = $callback();
        $subject->attach($container->get(Observer::class);
        return $subject;
    }
}
```

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

To prepare your existing factories for version 3, take the following steps:

- Implement the `__invoke()` method in your existing factory, copying the code
  from your existing `createService()` method into it.
- Modify the `createService()` method to proxy to the new method.

Consider the following factory that works for version 2:

```php
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FooFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $services)
    {
        return new Foo($services->get(Bar::class));
    }
}
```

To prepare this for version 3, we'd implement the `__invoke()` signature from
version 3, and modify `createService()` to proxy to it:

```php
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FooFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Foo($container->get(Bar::class));
    }

    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services, Foo::class);
    }
}
```

Note that the call to `$this()` adds a new argument; since your factory isn't
using the `$requestedName`, this can be anything, but must be passed to prevent
a fatal exception due to a missing argument. In this case, we chose to pass the
name of the class the factory is creating.

After you have upgraded to version 3, you can take the following steps to remove
the migration artifacts:

- Update your class to implement the new interface.
- Remove the `createService()` method from your implementation.

From our example above, we would update the class to read as follows:

```php
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface; // <-- note the change!

class FooFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Foo($container->get(Bar::class));
    }
}
```

> #### Many factories already work with v3!
>
> Within the skeleton application, tutorial, and even in commonly shipped
> modules such as those in Apigility, we have typically suggested building your
> factories as invokable classes. If you were doing this already, your factories
> will already work with version 3!

> #### Version 2 factories can accept the requested name already
>
> Since 2.2, factories have been passed two additional parameters, the
> "canonical" name (a mis-nomer, as it is actually the normalized name), and the
> "requested" name (the actual string passed to `get()`). As such, you can
> already write factories that accept the requested name, and have them
> change behavior based on that information!

### New InvokableFactory Class

`Zend\ServiceManager\Factory\InvokableFactory` is a new `FactoryInterface`
implementation that provides the capabilities of the "invokable classes" present
in version 2. It essentially instantiates and returns the requested class name;
if `$options` is non-empty, it passes them directly to the constructor.

This class was [added to the version 2 tree](https://github.com/zendframework/zend-servicemanager/pull/60)
to allow developers to start using it when preparing their code for version 3.
This is particularly of interest when creating plugin managers, as you'll
typically want the internal configuration to only include factories and aliases.

## Initializers

Initializers are still present in the Service Manager component, but exist
primarily for backwards compatibility; we recommend using delegator factories
for setter and interface injection instead of initializers, as those will be run
per-service, versus for all services.

For migration purposes, the original interface was retained, and now inherits
from the new interface. This provides a migration path; you can add the method
defined in the new interface to your existing initializers targeting v2, and
safely upgrade. (Typically, you will then have the version 2 method proxy to
the one defined in version 3.)

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

To prepare your existing initializers for version 3, take the following steps:

- Implement the `__invoke()` method in your existing factory, copying the code
  from your existing `initialize()` method into it.
- Modify the `initialize()` method to proxy to the new method.

As an example, consider this initializer for version 2:

```php
use Zend\ServiceManager\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FooInitializer implements InitializerInterface
{
    public function initializer($instance, ServiceLocatorInterface $services)
    {
        if (! $instance implements FooAwareInterface) {
            return $instance;
        }
        $instance->setFoo($services->get(FooInterface::class);
        return $instance;
    }
}
```

To prepare this for version 3, we'd implement the `__invoke()` signature from
version 3, and modify `initialize()` to proxy to it:

```php
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FooInitializer implements InitializerInterface
{
    public function __invoke(ContainerInterface $container, $instance)
    {
        if (! $instance implements FooAwareInterface) {
            return $instance;
        }
        $container->setFoo($services->get(FooInterface::class);
        return $instance;
    }

    public function initializer($instance, ServiceLocatorInterface $services)
    {
        return $this($services, $instance);
    }
}
```

After you have upgraded to version 3, you can take the following steps to remove
the migration artifacts:

- Update your class to implement the new interface.
- Remove the `initialize()` method from your implementation.

From our example above, we would update the class to read as follows:

```php
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Initializer\InitializerInterface; // <-- note the change!

class FooInitializer implements InitializerInterface
{
    public function __invoke(ContainerInterface $container, $instance)
    {
        if (! $instance implements FooAwareInterface) {
            return $instance;
        }
        $container->setFoo($services->get(FooInterface::class);
        return $instance;
    }
}
```

> ### Update your callables!
>
> Version 2 allows you to provide initializers as PHP callables. However, this
> means that the signature of those callables is incorrect for version 3!
>
> To make your code forwards compatible, you have two paths:
>
> The first is to simply provide an `InitializerInterface` implementation
> instead. This guarantees that the correct method is called based on the
> version of the `ServiceManager` in use.
>
> The second approach is to omit typehints on the arguments, and do typechecks
> internally. As an example, let's say you have the following:
>
> ```php
> $container->addInitializer(function ($instance, ContainerInterface $container) {
>      if (! $instance implements FooAwareInterface) {
>          return $instance;
>      }
>      $container->setFoo($services->get(FooInterface::class);
>      return $instance;
> });
> ```
>
> To make this future-proof, remove the typehints, and check the types within
> the callable:
>
> ```php
> $container->addInitializer(function ($first, $second) {
>      if ($first instanceof ContainerInterface) {
>          $container = $first;
>          $instance = $second;
>      } else {
>          $container = $second;
>          $instance = $first;
>      }
>      if (! $instance implements FooAwareInterface) {
>          return;
>      }
>      $container->setFoo($services->get(FooInterface::class);
> });
> ```
>
> This approach can also be done if you omitted typehints in the first place.
> Regardless, the important part to remember is that order of arguments is
> inverted between the two versions.

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
  capabilities for plugin managers. The class now has a (semi) *required*
  dependency on the application-level service manager instance, which is passed
  to all factories, abstract factories, etc. (More on this below.)

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

All plugin managers *must* implement this interface. For backwards-compatibility
purposes, `AbstractPluginManager` will check for the `validatePlugin()` method
(defined as abstract in v2), and, on discovery, trigger an `E_USER_DEPRECATED`
notice, followed by invocation of that method.

### AbstractPluginManager

As it did in version 2, `AbstractPluginManager` extends `ServiceManager`. **That
means that all changes made to the `ServiceManager` for v3 also apply to the
`AbstractPluginManager`.**

In addition, review the following changes.

#### Constructor

- The constructor now accepts the following arguments, in the following order:
  - The parent container instance; this is usually the application-level
    `ServiceManager` instance.
  - Optionally, an array of configuration for the plugin manager instance; this
    should have the same format as for a `ServiceManager` instance.
- `validatePlugin()` was renamed to `validate()` (now defined in
  `PluginManagerInterface`). The `AbstractPluginManager` provides
  a basic implementation (detailed below).
- The signature of `get()` changes (more below).

For backwards compatibility purposes, the constructor *also* allows the
following for the initial argument:

- A `null` value. In this case, the plugin manager will use itself as the
  creation context, *but also raise a deprecation notice indicating a
  container should be passed instead.* You can pass the parent container
  to the `setServiceLocator()` method to reset the creation context, but,
  again, this raises a deprecation notice.
- A `ConfigInterface` instance. In this case, the plugin manager will call
  the config instance's `toArray()` method to cast it to an array, and use the
  return value as the configuration to pass to the parent constructor. As with
  the `null` value, the plugin manager will be set as its own creation context.

#### Validation

The `validate()` method is defined as follows:

```php
public function validate($instance)
{
    if (method_exists($this, 'validatePlugin')) {
        trigger_error(sprintf(
            '%s::validatePlugin() has been deprecated as of 3.0; please define validate() instead',
            get_class($this)
        ), E_USER_DEPRECATED);
        $this->validatePlugin($instance);
        return;
    }

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

The two takeaways from this are:

- If you are upgrading from v2, your code should continue to work, *but will
  emit a deprecation notice*. The way to remove the deprecation notice is to
  rename the `validatePlugin()` method to `validate()`, or to remove it and
  define the `$instanceOf` property (if all you're doing is checking the
  plugin against a single typehint).
- Most plugin manager instances can simply define the `$instanceOf` property to
  indicate what plugin interface is considered valid for the plugin manager, and
  make no further changes to the abstract plugin manager:

```php
protected $instanceOf = ValidatorInterface::class;
```

#### get()

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

#### Deprecated methods

Finally, the following methods from v2's `ServiceLocatorAwareInterface` are
retained (without implementing the interface), but marked as deprecated:

- `setServiceLocator()`. This method exists as many tests and plugin manager
  factories were using it to inject the parent locator (now called the creation
  context). This method may still be used, and will now set the creation context
  for the plugin manager, but also emit a deprecation warning.
- `getServiceLocator()` is implemented in `ServiceManager` (from which
  `AbstractPluginManager` inherits), but marked as deprecated.

Regarding this latter point, `getServiceLocator()` exists to provide backwards
compatibility *for existing plugin factories*. These factories typically pull
dependencies from the parent/application container in order to initialize the
plugin. In v2, this would look like:

```php
function ($plugins)
{
    $services = $plugins->getServiceLocator();

    // pull dependencies from $services:
    $foo = $services->get('Foo');
    $bar = $services->get('Bar');

    return new Plugin($foo, $bar);
}
```

In v3, the initial argument to the factory is not the plugin manager instance,
but the *creation context*, which is analogous to the parent locator in v2. In
order to preserve existing behavior, we added the `getServiceLocator()` method
to the `ServiceManager`. As such, the above will continue to work in v3.

However, this method is marked as deprecated, and will emit an
`E_USER_DEPRECATED` notice. To remove the notice, you will need to upgrade your
code. The above example thus becomes:

```php
function ($services)
{
    // pull dependencies from $services:
    $foo = $services->get('Foo');
    $bar = $services->get('Bar');

    return new Plugin($foo, $bar);
}
```

If you *were* using the passed plugin manager and pulling other plugins, you
will need to update your code to retrieve the plugin manager from the passed
container. As an example, given this:

```php
function ($plugins)
{
    $anotherPlugin = $plugins->get('AnotherPlugin');
    return new Plugin($anotherPlugin);
}
```

You will need to rewrite it to:

```php
function ($services)
{
    $plugins = $services->get('PluginManager');
    $anotherPlugin = $plugins->get('AnotherPlugin');
    return new Plugin($anotherPlugin);
}
```

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

### Migration example

Let's consider the following plugin manager geared towards version 2:

```php
use RuntimeException;
use Zend\ServiceManager\AbstractPluginManager;

class ObserverPluginManager extends AbstractPluginManager
{
    protected $invokables = [
        'mail' => MailObserver::class,
        'log' => LogObserver::class,
    ];

    protected $shareByDefault = false;

    public function validatePlugin($instance)
    {
        if (! $instance instanceof ObserverInterface) {
            throw new RuntimeException(sprintf(
                'Invalid plugin "%s" created; not an instance of %s',
                get_class($instance),
                ObserverInterface::class
            ));
        }
    }
}
```

To prepare this for version 3, we need to do the following:

- We need to change the `$invokables` configuration to a combination of
  `factories` and `aliases`.
- We need to implement a `validate()` method.
- We need to update the `validatePlugin()` method to proxy to `validate()`.
- We need to add a `$sharedByDefault` property (if `$shareByDefault` is present).

Doing so, we get the following result:

```php
namespace MyNamespace;

use RuntimeException;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\ServiceManager\Factory\InvokableFactory;

class ObserverPluginManager extends AbstractPluginManager
{
    protected $instanceOf = ObserverInterface::class;

    protected $aliases = [
        'mail' => MailObserver::class,
        'Mail' => MailObserver::class,
        'log' => LogObserver::class,
        'Log' => LogObserver::class,
    ];

    protected $factories = [
        MailObserver::class       => InvokableFactory::class,
        LogObserver::class => InvokableFactory::class,
        // Legacy (v2) due to alias resolution
        'mynamespacemailobserver' => InvokableFactory::class,
        'mynamespacelogobserver'  => InvokableFactory::class,
    ];

    protected $shareByDefault = false;

    protected $sharedByDefault = false;

    public function validate($instance)
    {
        if (! $instance instanceof $this->instanceOf) {
            throw new InvalidServiceException(sprintf(
                'Invalid plugin "%s" created; not an instance of %s',
                get_class($instance),
                $this->instanceOf
            ));
        }
    }

    public function validatePlugin($instance)
    {
        try {
            $this->validate($instance);
        } catch (InvalidServiceException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
```

Things to note about the above:

- It introduces a new property, `$instanceOf`. We'll use this later, when we're
  ready to clean up post-migration.
- It introduces four aliases. This is to allow fetching the various plugins as
  any of `mail`, `Mail`, `log`, or `Log` &mdash; all of which are valid in
  version 2, but, because version 3 does not normalize names, need to be
  explicitly aliased.
- The aliases point to the fully qualified class name (FQCN) for the service
  being generated, and these are mapped to `InvokableFactory` instances. This
  means you can also fetch your plugins by their FQCN.
- There are also factory entries for the canonicalized FQCN of each factory,
  which will be used in v2. (Canonicalization in v2 strips non-alphanumeric
  characters, and casts to lowercase.)
- `validatePlugin()` continues to throw the old exception

The above will now work in both version 2 and version 3.

### Migration testing

To test your changes, create a new `MigrationTest` case that uses
`Zend\ServiceManager\Test\CommonPluginManagerTrait`. Override
`getPluginManager()` to return an instance of your plugin manager, and override
`getV2InvalidPluginException()` to return the classname of the exception your
`validatePlugin()` method throws:

```php
use MyNamespace\ObserverInterface;
use MyNamespace\ObserverPluginManager;
use MyNamespace\Exception\RuntimeException;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Test\CommonPluginManagerTrait;

class MigrationTest extends TestCase
{
    use CommonPluginManagerTrait;

    protected function getPluginManager()
    {
        return new ObserverPluginManager(new ServiceManager());
    }

    protected function getV2InvalidPluginException()
    {
        return RuntimeException::class;
    }

    protected function getInstanceOf()
    {
        return ObserverInterface::class;
    }
}
```

This will check that:

- You have set the `$instanceOf` property.
- `$shareByDefault` and `$sharedByDefault` match, if present.
- That requesting an invalid plugin throws the right exception.
- That all your aliases resolve.


### Post migration

After you migrate to version 3, you can clean up your plugin manager:

- Remove the `validatePlugin()` method.
- If your `validate()` routine is only checking that the instance is of a single
  type, and has no other logic, you can remove that implementation as well, as
  the `AbstractPluginManager` already takes care of that when `$instanceOf` is
  defined!
- Remove the canonicalized FQCN entry for each factory

Performing these steps on the above, we get:

```php
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Factory\InvokableFactory;

class ObserverPluginManager extends AbstractPluginManager
{
    protected $instanceOf = ObserverInterface::class;

    protected $aliases = [
        'mail' => MailObserver::class,
        'Mail' => MailObserver::class,
        'log' => LogObserver::class,
        'Log' => LogObserver::class,
    ];

    protected $factories = [
        MailObserver::class => InvokableFactory::class,
        LogObserver::class => InvokableFactory::class,
    ];
}
```

## DI Namespace

**The `Zend\ServiceManager\Di` namespace has been removed.**

The `Zend\Di` component is not actively maintained, and has been largely
deprecated during the ZF2 lifecycle in favor of the Service Manager. Its usage
as an abstract factory is problematic and error prone when used in conjunction
with the Service Manager; as such, we've removed it for the initial v3 release.

We may re-introduce it via a separate component in the future.

## Miscellaneous Interfaces, Traits, and Classes

The following interfaces, traits, and classes were *removed*:

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

The following classes and interfaces have changes:

- `Zend\ServiceManager\Proxy\LazyServiceFactory` is now marked `final`, and
   implements `Zend\ServiceManager\Proxy\DelegatorFactoryInterface`. Its
   dependencies and capabilities remain the same.
- `Zend\ServiceManager\ConfigInterface` now is expected to *return* the modified
  `ServiceManager` instance.
- `Zend\ServiceManager\Config` was updated to follow the changes to
  `ConfigInterface` and `ServiceManager`, and now returns the updated
  `ServiceManager` instance from `configureServiceManager()`.
