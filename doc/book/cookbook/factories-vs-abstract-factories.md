# When To Use Factories vs Abstract Factories

Starting with version 3, `Zend\ServiceManager\Factory\AbstractFactoryInterface`
extends `Zend\ServiceManager\Factory\FactoryInterface`, meaning they may be used
as either an abstract factory, or mapped to a specific service name as its
factory.

As an example:

```php
return [
	'factories' => [
		SomeService::class => AnAbstractFactory::class,
	],
];
```

Why would you choose one approach over the other?

## Comparisons

Approach         | Pros           | Cons
---------------- | -------------- | ----
Abstract factory | One-time setup | Performance; discovery of code responsible for creating instance
Factory          | Performance; explicit mapping to factory responsible | Additional (duplicate) setup

Essentially, it comes down to *convenience* versus *explicitness* and/or
*performance*.

## Conveneience

Writing a factory per service is time consuming, and, particularly in early
stages of an application, can distract from the actual business of writing the
classes and implementations; in addition, since requirements are often changing
regularly, this boiler-plate code can be a nuisance.

In such situations, one or more abstract factories &mdash; such as the
[ConfigAbstractFactory](../config-abstract-factory.md) or the
[zend-mvc LazyControllerAbstractFactory](https://docs.zendframework.com/zend-mvc/cookbook/automating-controller-factories/)
&mdash; that can handle the bulk of your needs are often worthwhile, saving you
time and effort as you code.

## Explicitness

The drawback of abstract factories is that lookups by the service manager take
longer, and increase based on the number of abstract factories in the system.
The service manager is optimized to locate *factories*, as it can do an
immediate hash table lookup; abstract factories involve:

- Looping through each abstract factory
    - invoking its method for service location
    - if the service is located, using the factory

This means, internally:

- a hash table lookup (for the abstract factory)
- invocation of 1:N methods for discovery
    - which may contain additional lookups and/or retrievals in the container
- invocation of a factory method (assuming succesful lookup)

As such, having an explicit map can aid performance dramatically.

Additionally, having an explicit map can aid in understanding what class is
responsible for initializing a given service. Without an explicit map, you need
to identify all possible abstract factories, and determine which one is capable
of handling the specific service; in some cases, multiple factories might be
able to, which means you additionally need to know the *order* in which they
will be queried.

The primary drawback is that you also end up with potentially duplicate
information in your configuration:

- Multiple services mapped to the same factory.
- In cases such as the `ConfigAbstractFactory`, additional configuration
  detailing how to create the service.

## Tradeoffs

What it comes down to is which development aspects your organization or project
favor. Hopefully the above arguments detail what tradeoffs occur, so you may
make an appropriate choice.

> ### Tooling
>
> We will likely provide tooling in the future to convert
> `ConfigAbstractFactory` configuration into discrete factory classes in the
> future, allowing you to mitigate the performance issues of using an abstract
> factory when in production. As such, tooling support should also be a
> consideration when deciding on your project strategy.
