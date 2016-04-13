# Configuring the service manager

The Service Manager component can be configured by passing an associative array to the component's
constructor. The following keys are:

- `services`: associative array that maps a key to a service instance.
- `factories`: associative array that map a key to a factory name, or any callable.
- `abstract_factories`: a list of abstract factories classes. An abstract
  factory is a factory that can potentially create any object, based on some
  criterias.
- `delegators`: TODO (delegators are described in their own section).
- `aliases`: associative array that map a key to a service key (or another alias).
- `initializers`: a list of callable or initializers that are run whenever a service has been created.
- `shared`: associative array that map a service name to a boolean, in order to
  indicate the service manager if it should cache or not a service created
  through the `get` method, independant of the `shared_by_default` setting.
- `lazy_services`: configuration for the lazy service proxy manager, and a class
  map of service:class pairs that will act as lazy services; see the
  [lazy services documentation](lazy-services.md) for more details.
- `shared_by_default`: boolean that indicates whether services created through
  the `get` method should be cached. This is true by default.

Here is an example of how you could configure a service manager:

```php
use Zend\ServiceManager\ServiceManager;

$serviceManager = new ServiceManager([
    'services'           => [],
    'factories'          => [],
    'abstract_factories' => [],
    'delegators'         => [],
    'shared'             => [],
    'shared_by_default'  => true
]);
```

## Factories

A factory is any callable or any class that implements the interface
`Zend\ServiceManager\Factory\FactoryInterface`.

Service manager components provide a default factory that can be used to create
objects that do not have any dependencies:

```php
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use stdClass;

$serviceManager = new ServiceManager([
    'factories' => [
        stdClass::class => InvokableFactory::class
    ]
]);
```

> This mechanism replaces the `invokables` key that was used in Zend Framework 2.

As said before, a factory can also be a callable, to create more complex objects:

```php
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use stdClass;

$serviceManager = new ServiceManager([
    'factories' => [
        stdClass::class => InvokableFactory::class,
        MyObject::class => function(ContainerInterface $container, $requestedName) {
            $dependency = $container->get(stdClass::class);
            return new MyObject($dependency);
        },
    ],
]);
```

Each factory always receive a `ContainerInterface` argument (this is the base
interface that the `ServiceManager` implements), as well as the requested name
as the second argument. In this case, the `$requestedName` is `MyObject`.

Alternatively, the above code can be replaced by a factory class instead of a
closure. This leads to more readable code. For instance:

```php
// In MyObjectFactory.php file

class MyObjectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dependency = $container->get(stdClass::class);
        return new MyObject($dependency);
    }
}

// When creating the service manager:
$serviceManager = new ServiceManager([
    'factories' => [
        stdClass::class => InvokableFactory::class,
        MyObject::class => MyObjectFactory::class
    ]
]);
```

> For performance reasons, factories objects are not created until requested.
> In the above example, this means that the `MyObjectFactory` object won't be
> created until `MyObject` is requested.

### Mapping multiple service to the same factory

Unlike version 2 implementations of the component, in the version 3
implementation, the `$requestedName` is guaranteed to be passed as the second
parameter of a factory. This is useful when you need to create multiple
services that are created exactly the same way, hence reducing the number of
needed factories.

For instance, if two services share the same creation pattern, you could attach the same factory:

```php
// In MyObjectFactory.php file

class MyObjectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dependency = $container->get(stdClass::class);
        return new $requestedName($dependency);
    }
}

// When creating the service manager:
$serviceManager = new ServiceManager([
    'factories' => [
        MyObjectA::class => MyObjectFactory::class,
        MyObjectB::class => MyObjectFactory::class
    ]
]);
```

This pattern can often replace abstract factories, and is more performant:

- Lookups for services do not need to query abstract factories; the service is
  mapped explicitly.
- Once the factory is loaded for any object, it stays in memory for any other
  service using the same factory.

Using factories is recommended in most cases where abstract factories were used
in version 2.

This feature *can* be abused, however: for instance, if you have dozens of
services that share the same creation, but which do not share any common
functionality, we recommend to create separate factories.

## Abstract factories

An abstract factory is a specialized factory that can be used to create any
service, if it has the capability to do so. An abstract factory is often useful
when you do not know in advance the name of the service (e.g. if the service
name is generated dynamically at runtime), but know that the services share a
common creation pattern.

An abstract factory must be registered inside the service manager, and is
checked if no factory can create an object. Each abstract factory must
implement `Zend\ServiceManager\Factory\AbstractFactoryInterface`:

```php
// In MyAbstractFactory.php:

class MyAbstractFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return in_array('Traversable', class_implements($requestedName), true);
    }
    
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return $requestedName();
    }
}

// When creating the service manager:
$serviceManager = new ServiceManager([
    'abstract_factories' => [
        new MyAbstractFactory() // You could also pass a class name: MyAbstractFactory::class
    ]
]);

// When fetching an object:
$object = $serviceManager->get(A::class);
```

Here is what will happen:

1. The service manager will check if it contains a factory mapped to the
   `A::class` service.
2. Because none is found, it will process each abstract factory, in the order
   in which they were registered.
3. It will call the `canCreate()` method, passing the service manager instance and
   the name of the requested object. The method can use any logic whatsoever to
   determine if it can create the service (such as checking its name, checking
   for a required dependency in the passed container, checking if a class
   implements a given interface, etc.).
4. If `canCreate()` returns `true`, it will call the `__invoke` method to
   create the object. Otherwise, it will continue iterating the abstract
   factories, until one matches, or the queue is exhausted.

### Best practices

While convenient, we recommend you to limit the number of abstract factories.
Because the service manager needs to iterate through all registered abstract
factories to resolve services, it can be costly when multiple abstract
factories are present.

Often, mapping the same factory to multiple services can solve the issue more
efficiently (as described in the `Factories` section).

## Aliases

An *alias* provides an alternative name for a registered service.

An alias can also be mapped to another alias (it will be resolved recursively).
For instance:

```php
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use stdClass;

$serviceManager = new ServiceManager([
    'factories' => [
        stdClass::class => InvokableFactory::class
    ],
    
    'aliases' => [
        'A' => stdClass::class,
        'B' => 'A'
    ]
]);

$object = $serviceManager->get('B');
```

In this example, asking `B` will be resolved to `A`, which will be itself
resolved to `stdClass::class`, which will finally be constructed using the
provided factory.

### Best practices

We recommend you minimal use of aliases, and instead using the `::class`
language construct to map using a FQCN (Fully-Qualified-Class-Name). This
provides both better discoverability within your code, and allows simpler
refactoring, as most modern IDEs can refactor class names specified using the
`::class` keyword.

## Initializers

An initializer is any callable or any class that implements the interface
`Zend\ServiceManager\Initializer\InitializerInterface`. Initializers are
executed for each service the first time they are created, and can be used to
inject additional dependencies.

For instance, if we'd want to automatically inject the dependency
`EventManager::class` in all objects that implement the interface
`EventManagerAwareInterface`, we could create the following initializer:

```php
use Interop\Container\ContainerInterface;
use stdClass;
use Zend\ServiceManager\ServiceManager;

$serviceManager = new ServiceManager([
    'initializers' => [
        function(ContainerInterface $container, $instance) {
            if (! $instance instanceof EventManagerAwareInterface) {
                return;
            }
            $instance->setEventManager($container->get(EventManager::class));
        }
    ]
]);
```

Alternately, you can create a class that implements
`Zend\ServiceManager\Initializer\InitializerInterface`, and pass it to the
`initializers` array:

```php
// In MyInitializer.php

class MyInitializer implements InitializerInterface
{
    public function __invoke(ContainerInterface $container, $instance)
    {
        if (! $instance instanceof EventManagerAwareInterface) {
            return;
        }
        $instance->setEventManager($container->get(EventManager::class));
    }
}

// When creating the service manager:

use Interop\Container\ContainerInterface;
use stdClass;
use Zend\ServiceManager\ServiceManager;

$serviceManager = new ServiceManager([
    'initializers' => [
        new MyInitializer() // You could also use MyInitializer::class
    ]
]);
```

> Note that initializers are automatically created when the service manager is
> initialized, even if you pass a class name.

### Best practices

While convenient, initializer usage is also problematic. They are provided
primarily for backwards compatibility, but we highly discourage their usage.

The primary issues with initializers are:

- They lead to fragile code. Because the dependency is not injected directly in
  the constructor, it means that the object may be in an "incomplete state". If
  for any reason the initializer is not run (if it was not correctly registered
  for instance), bugs ranging from the subtle to fatal can be introduced.
  
  Instead, we encourage you to inject all necessary dependencies via
  the constructor, using factories. If some dependencies use setter or interface
  injection, use delegator factories.
  
  If a given service has too many dependencies, then it may be a sign that you
  need to split this service into smaller, more focused services.

- They are slow: an initializer is run for EVERY instance you create through
  the service manager. If you have ten initializers or more, this can quickly
  add up!

## Shared

By default, a service created is shared. This means that calling the `get()`
method twice for a given service will return exactly the same service. This is
typically what you want, as it can saves a lot of memory and increase
performance:

```php
$serviceManager = new ServiceManager([
    'factories' => [
        stdClass::class => InvokableFactory::class
    ]
]);

$object1 = $serviceManager->get(stdClass::class);
$object2 = $serviceManager->get(stdClass::class);

var_dump($object1 === $object2); // prints "true"
```

However, occasionally you may require discrete instances of a service. To
enable this, you can use the `shared` key, providing a boolean false value for
your service, as shown below:

```php
$serviceManager = new ServiceManager([
    'factories' => [
        stdClass::class => InvokableFactory::class
    ],
    'shared' => [
        stdClass::class => false
    ]
]);

$object1 = $serviceManager->get(stdClass::class);
$object2 = $serviceManager->get(stdClass::class);

var_dump($object1 === $object2); // prints "false"
```

Alternately, you can use the `build()` method instead of the `get()` method.
The `build()` method works exactly the same as the `get` method, but never
caches the service created, nor uses a previously cached instance for the
service.

```php
$serviceManager = new ServiceManager([
    'factories' => [
        stdClass::class => InvokableFactory::class
    ]
]);

$object1 = $serviceManager->build(stdClass::class);
$object2 = $serviceManager->build(stdClass::class);

var_dump($object1 === $object2); // prints "false"
```

Finally, you could also decide to disable caching by default (even when calling
the `get()` method), by setting the `shared_by_default` option to false:

```php
$serviceManager = new ServiceManager([
    'factories' => [
        stdClass::class => InvokableFactory::class
    ],
    'shared_by_default' => false,
]);

$object1 = $serviceManager->get(stdClass::class);
$object2 = $serviceManager->get(stdClass::class);

var_dump($object1 === $object2); // prints "false"
```

## Passing config to a factory/delegator

So far, we have covered examples where services are created through factories
(or abstract factories). The factory is able to create the object itself.

Occasionally you may need to pass additional options that act as a "context".
For instance, we could have a `StringLengthValidator` service registered.
However, this validator can have multiple options, such as `min` and `max`.
Because this is dependant on the caller context (or might even be retrieved
from a database, for instance), the factory cannot know what options to give
when constructing the validator.

To solve this issue, the service manager offers a `build()` method. It works
similarly to the `get()` method, with two main differences:

- Services created with the `build()` method are **never cached**, nor pulled
  from previously cached instances for that service.
- `build()` accepts an optional secondary parameter, an array of options.

Those options are transferred to all factories, abstract factories, and delegators.
For instance:

```php
// In StringLengthValidatorFactory.php

class StringLengthValidatorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = [])
    {
        return new StringLengthValidator($options);
    }
}

// When creating the service manager:
$serviceManager = new ServiceManager([
    'factories' => [
        StringLengthValidator::class => StringLengthValidatorFactory::class
    ]
]);

// When creating the objects:

$validator1 = $serviceManager->build(StringLengthValidator::class, ['min' => 5]);
$validator2 = $serviceManager->build(StringLengthValidator::class, ['min' => 15]);
```

In our previous example, because the `StringLengthValidator` does not have any
other dependencies other than the `$options`, we could remove the factory, and
simply map it to the built-in `InvokableFactory` factory:

```php
// When creating the service manager:
$serviceManager = new ServiceManager([
    'factories' => [
        StringLengthValidator::class => InvokableFactory::class
    ]
]);

// When creating the objects:

$validator1 = $serviceManager->build(StringLengthValidator::class, ['min' => 5]);
$validator2 = $serviceManager->build(StringLengthValidator::class, ['min' => 15]);
```

This works because the `InvokableFactory` will automatically pass the options
(if any) to the constructor of the created object.

## Altering a service manager's config

Assuming that you have not called `$container->setAllowOverride(false)`, you can,
at any time, configure the service manager with new services using any of the
following methods:

- `configure()`, which accepts the same configuration array as the constructor.
- `setAlias($alias, $target)`
- `setInvokableClass($name, $class = null)`; if no `$class` is passed, the
  assumption is that `$name` is the class name.
- `setFactory($name, $factory)`, where `$factory` can be either a callable
  factory or the name of a factory class to use.
- `mapLazyService($name, $class = null)`, to map the service name `$name` to
  `$class`; if the latter is not provided, `$name` is used for both sides of
  the map.
- `addAbstractFactory($factory)`, where `$factory` can be either a
  `Zend\ServiceManager\Factory\AbstractFactoryInterface` instance or the name
  of a class implementing the interface.
- `addDelegator($name, $factory)`, where `$factory` can be either a callable
  delegator factory, or the name of a delegator factory class to use.
- `addInitializer($initializer)`, where `$initializer` can be either a callable
  initializer, or the name of an initializer class to use.
- `setService($name, $instance)`
- `setShared($name, $shared)`, where `$shared` is a boolean flag indicating
  whether or not the named service should be shared.

As examples:

```php
use Zend\ServiceManager\ServiceManager;

$serviceManager = new ServiceManager([
    'factories' => [
        stdClass::class => InvokableFactory::class;
    ]
]);

$serviceManager->configure([
    'factories' => [
        DateTime::class => InvokableFactory::class
    ]
]);

var_dump($newServiceManager->has(DateTime::class)); // prints true

// Create an alias from 'Date' to 'DateTime'
$serviceManager->setAlias('Date', DateTime::class);

// Set a factory for the 'Time' service
$serviceManager->setFactory('Time', function ($container) {
    return $container->get(DateTime::class);
});

// Map a lazy service named 'localtime' to the class DateTime.
$serviceManager->mapLazyService('localtime', DateTime::class);

// Add an abstract factory
$serviceManager->addAbstractFactory(new CustomAbstractFactory());

// Add a delegator factory for the DateTime service
$serviceManager->addDelegator(DateTime::class, function ($container, $name, $callback) {
    $dateTime = $callback();
    $dateTime->setTimezone(new DateTimezone('UTC'));
    return $dateTime;
});

// Add an initializer
// Note: don't do this. Use delegator factories instead.
$serviceManager->addInitializer(function ($service, $instance) {
    if (! $instance instanceof DateTime) {
        return;
    }
    $instance->setTimezone(new DateTimezone('America/Chicago'));
})

// Explicitly map a service name to an instance.
$serviceManager->setService('foo', new stdClass);

// Mark the DateTime service as NOT being shared.
$serviceManager->setShared(DateTime::class, false);
```
