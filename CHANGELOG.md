# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## v3.0.0 - TBD

### Added

* You can now map multiple key names to the same factory. It was previously possible in ZF2 but it was not enforced
by the `FactoryInterface` interface. Now, this interface receives the `$requestedName` as second parameter.

Example:

```php
$sm = new \Zend\ServiceManager\ServiceManager([
  'factories'  => [
    MyClassA::class => MyFactory::class,
    MyClassB::class => MyFactory::class
  ]
]);

$sm->get(MyClassA::class); // MyFactory will receive MyClassA::class as second parameter
```

* Writing a plugin manager has been simplified. If you have simple needs, you no longer need to implement the complete
`validate` method.

In ZF 2.x, if your plugin manager only accepts to create instances that implement `Zend\Validator\ValidatorInterface`,
you needed to write this code:

```php
class MyPluginManager extends AbstractPluginManager
{
  public function validate($instance)
  {
    if ($instance instanceof \Zend\Validator\ValidatorInterface) {
      return;
    }

    throw new InvalidServiceException(sprintf(
      'Plugin manager "%s" expected an instance of type "%s", but "%s" was received',
       __CLASS__,
       \Zend\Validator\ValidatorInterface::class,
       is_object($instance) ? get_class($instance) : gettype($instance)
    ));
  }
}
```

In ZF 3.x:

```php
class MyPluginManager extends AbstractPluginManager
{
  protected $instanceOf = \Zend\Validator\ValidatorInterface::class;
}
```

Of course, you can still override `validate` method if your logic is more complex.

### Deprecated

* Nothing

### Removed

* Peering has been removed. It was a complex and rarely used feature that was misunderstood most of the time.

* Integration with `Zend\Di` has been removed. It may be re-integrated later as part of another component.

* `invokables` key no longer exists. It has been replaced by a built-in factory.

In ZF 2.x:

```php
return [
  'service_manager' => [
    'invokables' => [
      MyClass::class => MyClass:class
    ]
  ]
];
```

In ZF 3.x:

```php
return [
  'service_manager' => [
    'factories' => [
      MyClass::class => \Zend\ServiceManager\Factory\InvokableFactory:class
    ]
  ]
];
```

* `MutableCreationOptionsInterface` has been removed, as options can now be passed directly through factories. (??)

* `ServiceLocatorAwareInterface` and its associated trait has been removed. It was an anti-pattern, and you are encouraged
to inject your dependencies in factories instead of injecting the whole service locator.

### Changed/Fixed

v3 of the ServiceManager component is a completely rewritten, more efficient implementation of the service locator
pattern. It includes a number of breaking changes, that are outlined in this section.

* You no longer need a `Zend\ServiceManager\Config` object to configure the service manager, but you instead need to
simply pass an array.

In ZF 2.x:

```php
$config = new \Zend\ServiceManager\Config([
  'factories'  => [...]
]);

$sm = new \Zend\ServiceManager\ServiceManager($config);
```

In ZF 3.x:

```php
$sm = new \Zend\ServiceManager\ServiceManager([
  'factories'  => [...]
]);
```

* Service manager is now immutable. Once configured, it cannot be altered. You need to create a new service manager
if you need to change the configuration. This allow to ensure safer and more aggressive caching.

* Interfaces for `FactoryInterface`, `DelegatorFactoryInterface` and `AbstractFactoryInterface` have changed. Now,
they are all callable. This allow to optimize performance. Most of the time, rewriting a factory to match new interface
implies replacing the method name by `__invoke`.

For instance, here is a simple ZF 2.x factory:

```php
class MyFactory implements FactoryInterface
{
  function createService(ServiceLocatorInterface $sl)
  {
    // ...
  }
}
```

The equivalent ZF 3.x factory:

```php
class MyFactory implements FactoryInterface
{
  function __invoke(ServiceLocatorInterface $sl, $requestedName)
  {
    // ...
  }
}
```

As you can see, factories also receive a second parameter enforce through interface, that allows to easily map multiple
service names to the same factory.

* Plugin managers will now receive the parent service locator instead of itself in factories. In ZF 2.x, you needed
to call the method `getServiceLocator` to retrieve the main service locator. This was confusing, and was not IDE friendly
as this method was not enforced through interface.

In ZF 2.x, if a factory was set to a service name defined in a plugin manager:

```php
class MyFactory implements FactoryInterface
{
  function createService(ServiceLocatorInterface $sl)
  {
    // $sl is actually a plugin manager

    $parentLocator = $sl->getServiceLocator();

    // ...
  }
}
```

In ZF 3.x:

```php
class MyFactory implements FactoryInterface
{
  function __invoke(ServiceLocatorInterface $sl, $requestedName)
  {
    // $sl is already the main, parent service locator. If you need to retrieve the plugin manager again, you
    // can retrieve it through the SL
    $pluginManager = $sl->get(MyPluginManager::class);
    // ...
  }
}
```

In practice, this should reduce code as dependencies often come from the main service locator, and not the plugin
manager itself.

* `PluginManager` now enforce the need for the main service locator in their constructor. In ZF2, people often forgot
to set the parent locator, which leads to bugs in factories trying to fetch dependencies from the parent locator.

* It's so fast now that your app will fly.

## 2.6.0 - TBD

### Added

- [#4](https://github.com/zendframework/zend-servicemanager/pull/4) updates the
    `ServiceManager` to [implement the container-interop interface](https://github.com/container-interop/container-interop),
    allowing interoperability with applications that consume that interface.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.5.2 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#3](https://github.com/zendframework/zend-servicemanager/pull/3) properly updates the
  codebase to PHP 5.5, by taking advantage of the default closure binding
  (`$this` in a closure is the invoking object when created within a method). It
  also removes several `@requires PHP 5.4.0` annotations.