# PSR-11 Support

[container-interop/container-interop 1.2.0](https://github.com/container-interop/container-interop/releases/tag/1.2.0)
modifies its codebase to extend interfaces from [psr/container](https://github.com/php-fig/container)
(the official interfaces for [PSR-11](http://www.php-fig.org/psr/psr-11/)). If
you are on a pre-3.3.0 version of zend-servicemanager, update your project, and
receive container-interop 1.2, then zend-servicemanager can already act as a
PSR-11 provider!

zend-servicemanager 3.3.0 requires at least version 1.2 of container-interop,
and _also_ requires psr/container 1.0 to explicitly signal that it is a PSR-11
provider, and to allow removal of the container-interop dependency later.

Version 4.0 will require only psr/container, and will update the various factory
interfaces and exception implementations to typehint against the PSR-11
interfaces, which will require changes to any implementations you have. In the
meantime, you can [duck-type](https://en.wikipedia.org/wiki/Duck_typing) the
following factory types:

- `Zend\ServiceManager\Factory\FactoryInterface`: use a callable with the
  following signature:

  ```php
  function (
      \Psr\Container\ContainerInterface $container,
      string $requestedName,
      array $options = null
  )
  ```

- `Zend\ServiceManager\Factory\DelegatorFactoryInterface`: use a callable with
  the following signature:

  ```php
  function (
      \Psr\Container\ContainerInterface $container,
      string $name,
      callable $callback,
      array $options = null
  )
  ```

- `Zend\ServiceManager\Initializer\InitializerInterface`: use a callable with
  the following signature:

  ```php
  function (
      \Psr\Container\ContainerInterface $container,
      $instance
  )
  ```

Abstract factories _can not_ be duck typed, due to the additional `canCreate()`
method.

You can also leave your factories as-is for now, and update them once
zend-servicemanager v4.0 is released, at which time we will be providing tooling
to help migrate your factories to PSR-11.
