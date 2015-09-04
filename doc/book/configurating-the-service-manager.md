# Configuring the service manager

The Service Manager component can be configured by passing an associative array to the component's
constructor. The following keys are:

* `factories`: associative array that map a key to a factory name, or any callable.
* `abstract_factories`: a list of abstract factories classes. An abstract factory is a factory that can potentially
create any object, based on some criterias.
* `delegators`: TODO (delegators are described in their own section).
* `aliases`: associative array that map a key to a service key (or another alias).
* `initializers`: a list of callable or initializers that are run whenever a service has been created.
* `shared`: associative array that map a service name to a boolean, in order to indicate the service manager if
it should cache or not a service created through the `get` method, independant of the `shared_by_default` setting.
* `shared_by_default`: boolean that indicates whether services created through the `get` method should be cached. This
is true by default.

Here is an example of how you could configure a service manager:

```php
use Zend\ServiceManager\ServiceManager;

$serviceManager = new ServiceManager([
    'factories' => [],
    'abstract_factories' => [],
    'delegators' => [],
    'shared' => [],
    'shared_by_default' => true
]);
```

## Factories

A factory is any callable (class that implements `__invoke` method) or any class that implements the interface
`Zend\ServiceManager\Factory\FactoryInterface`.

Service manager components provide a default factory that can be used to create objects that do not have
any dependencies:

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
        }
    ]
]);
```

Each factory always receive a `ContainerInterface` container (this is the base interface that the `ServiceManager`
implements), as well as the requested name as second parameter. In this case, the `$requestedName` is `MyObject`.

Alternatively, the above code can be replaced by a factory class instead of a closure. This leads to more
readable code. For instance:

```php
// In MyObjectFactory.php file

class MyObjectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName)
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

> For performance reasons, factories objects are not created until requested. In the above example, this means that the
`MyObjectFactory` object won't be created until `MyObject` is requested.

### Mapping multiple service to the same factory

Contrary to Zend Framework 2, in this new implementation, the `$requestedName` is guaranteed to be passed as the
second parameter of a factory. This is useful when you need to create multiple services that are created exactly
the same way, hence reducing the number of needed factories.

For instance, if two services share the same creation pattern, you could attach the same factory:

```php
// In MyObjectFactory.php file

class MyObjectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName)
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

This pattern can often replace abstract factories. However, as a best practice, we do not recommend to abuse this. For
instance, if you have dozens of services that are created the same but do not share anything in common functionality wise,
we recommend to create separate factories.

## Abstract factories

An abstract factory is a specialized factory that can be used to create any service, if it has the capability to do so. An
abstract factory is often useful when you do not know in advance the name of the service (ie. if they are generated
automatically at runtime), but that share a common creation pattern.

An abstract factory must be registered inside the service manager, and is checked if no factory can create an object. Each
abstract factory must implement the `Zend\ServiceManager\Factory\AbstractFactoryInterface`:

```php
// In MyAbstractFactory.php:

class MyAbstractFactory implements AbstractFactoryInterface
{
    public function canCreateServiceWithName($requestedName)
    {
        return $requestedName implements Traversable;
    }
    
    public function __invoke(ContainerInterface $container, $requestedName)
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

1. Service manager will check if it contains a factory mapped to the `A::class` service.
2. Because none is found, it will process each abstract factory, in the order they were registered.
3. It will call the `canCreateServiceWithName` method, passing the name of the requested object. The method could perform
any logic to detect if it can handle the creation of this service (like checking its name, or if it implements a given interface).
4. If `canCreateServiceWithName` returns true, it will call the `__invoke` method to create the object. Otherwise, it will check
the next abstract factories, until they are all exhausted.

### Best practices

While convenient, we recommend you to limit the number of abstract factories. Because service manager needs to iterate through
all registered abstract factories, it can be costly if you have dozens of abstract factories.

Often, mapping the same factory to multiple services can solve the issue more efficiently (as described in the `Factories` section).

## Aliases

An alias is an alternative name for a service, so that a service can be retrieved using this alias instead of the original name. An
alias can also be mapped to another alias (it will be resolved recursively). For instance:

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

In this example, asking `B` will be resolved to `A`, which will be itself resolved to `stdClass::class`, which will finally be
constructed using the set factory.

### Best practices

We recommend you to minimize the usage of aliases, and instead using the `::class` language construct to map FQCN (Fully-Qualified-Class-Name). Not
only this allows better discoverability of your code, but it also allows simpler refactoring, as modern IDE can refactor class names specified
using the `::class` keyword.

## Initializers

An initializer is any callable (class that implements `__invoke` method) or any class that implements the interface
`Zend\ServiceManager\Initializer\InitializerInterface`. They are executed for each created service, and can be used
to inject additional dependencies.

For instance, if we'd want to automatically inject a dependency `EventManager::class` to all objects that would implement the interface
`EventManagerAwareInterface`, we could create our own initializer like this:

```php
use Interop\Container\ContainerInterface;
use stdClass;
use Zend\ServiceManager\ServiceManager;

$serviceManager = new ServiceManager([
    'initializers' => [
        function(ContainerInterface $container, $instance) {
            if ($instance instanceof EventManagerAwareInterface) {
                $instance->setEventManager($container->get(EventManager::class));
            }
        }
    ]
]);
```

Alternatively, you can create a class that implements `Zend\ServiceManager\Initializer\InitializerInterface`, and pass
it to the `initializers` array:

```php
// In MyInitializer.php

class MyInitializer implements InitializerInterface
{
    public function __invoke(ServiceLocatorInterface $serviceLocator, $instance)
    {
        if ($instance instanceof EventManagerAwareInterface) {
            $instance->setEventManager($container->get(EventManager::class));
        }
    }
}

// When creating the service manager:

use Interop\Container\ContainerInterface;
use stdClass;
use Zend\ServiceManager\ServiceManager;

$serviceManager = new ServiceManager([
    'initializers' => [
        new MyInitializer() // You could also use a class name: MyInitializer::class
    ]
]);
```

> Note that initializers are automatically created when the service manager is initialized, even if you
pass a class name

### Best practices

While convenient, initializers suffer from a lot of problems. They are mostly here as a compatibility with
previous versions of the service manager, but we highly discourage its usage. The biggest weaknesses are:

* It leads to fragile code: because the dependency is not injected directly in the constructor, it means that
the object may be in an "incomplete state". If for any reason the initializer is not run (if it was not correctly
registered for instance), it can leads to hard to spot bugs. Instead, we encourage you to always inject all
the needed dependencies into the constructor, through the usage of factories. If a given service has too many
dependencies, then it may be a sign that you need to split this service into smaller, more focused services.
* They are slow: an initializer is run for EVERY object that you create through the service manager. If you have
ten initializers or more, this can quickly add up!

## Shared

By default, a service created is shared. This means that calling the `get` method twice for a given service
will return exactly the same service. This is actually often what you want, as it can saves a lot of memory and
increase performance:

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

However, some time, you may want to never share a service. To that extent, you could use the `shared` key, and set
the false boolean value to the service name, as shown below:

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

Alternatively, you could also use the `build` method instead of the `get` method. The `build` method works similarily
to the `get` method, but ensure that created services are never cached:

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

Finally, you could also decide to disable caching by default (even when calling the `get` method), by setting the
`shared_by_default` option to false:

```php
$serviceManager = new ServiceManager([
    'factories' => [
        stdClass::class => InvokableFactory::class
    ],
    'shared_by_default' => false
]);

$object1 = $serviceManager->get(stdClass::class);
$object2 = $serviceManager->get(stdClass::class);

var_dump($object1 === $object2); // prints "false"
```

## Passing config to a factory/delegator

So far, we have covered examples where services are created through factories (or abstract factories). The factory
is able to create itself the object.

However, some time, you may need to pass additional options that act as a "context". For instance, we could have a
`StringLengthValidator` service registered. However, this validator can have multiple options, like `min` and `max`. Obviously,
because this is dependant on the caller context (or may even be retrieved from a database, for instance), the factory
cannot know what options to give when constructing the validator.

To solve this issue, the service manager offers a new `build` method. It works similarly to the `get` method, with two
main differences:

* Services created with the `build` method are **never cached**.
* `build` accepts an optional secondary parameter, that contain the options.

Those options are transferred to all factories, abstract factories, delegators. For instance:

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

In our previous example, because the `StringLengthValidator` does not have any other dependencies other
than the `$options`, we could remove the factory, and simply map it to the built-in `InvokableFactory` factory:

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

This works because the `InvokableFactory` will automatically pass the options (if any) to the constructor
of the created object.

## Altering a service manager's config

Zend Framework service manager is an immutable structure. This means that once you have created a service manager,
you cannot alter its configuration. We are doing this for performance reasons, as it allows us to more aggressively
cache various things.

However, you may need to alter its configuration at runtime in order to add new factories, for instance. To do that,
you can use the `withConfig` method. This will create a new service manager, whose configuration is merged with
the additional configuration:

```php
use Zend\ServiceManager\ServiceManager;

$serviceManager = new ServiceManager([
    'factories' => [
        stdClass::class => InvokableFactory::class;
    ]
]);

$newServiceManager = $serviceManager->withConfig([
    'factories' => [
        DateTime::class => InvokableFactory::class
    ]
]);

var_dump($serviceManager->has(DateTime::class)); // prints false
var_dump($newServiceManager->has(DateTime::class)); // prints true
```

As you can see from this example, the old service manager has been untouched, and therefore does not contain
any factory set for the `DateTime` service.

> When creating a new service manager through the `withConfig` method, all services that were created with the
old service manager **are not** cloned.