# Lazy Services

`Zend\ServiceManager` can use [delegator factories](delegators.md) to generate
"lazy" references to your services.

Lazy services are [proxies](http://en.wikipedia.org/wiki/Proxy_pattern) that
get lazily instantiated, and keep a reference to the real instance of
the proxied service.

## Use cases

You may want to lazily initialize a service when it is instantiated very often,
but not always used.

A typical example is a database connection: it is a dependency to many other
elements in your application, but that doesn't mean that every request will
execute queries through it.

Additionally, instantiating a connection to the database may require some time
and eat up resources.

Proxying the database connection would allow to delay that overhead until the
object is really needed.

## Setup

`Zend\ServiceManager\Proxy\LazyServiceFactory` is a [delegator factory](delegators.md)
capable of generating lazy loading proxies for your services.

The lazy service facilities depend on [ProxyManager](https://github.com/Ocramius/ProxyManager);
you will need to install that package before using the feature:

```php
$ composer require ocramius/proxy-manager
```

## Practical example

To demonstrate how a lazy service works, you may use the following `Buzzer`
example class, which is designed to be slow at instantiation time for
demonstration purposes:

```php
namespace MyApp;

class Buzzer
{
    public function __construct()
    {
        // deliberately halting the application for 5 seconds
        sleep(5);
    }

    public function buzz()
    {
        return 'Buzz!';
    }
}
```

You can then proceed and configure the service manager to generate proxies
instead of real services:

```php
use MyApp\Buzzer;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\Proxy\LazyServiceFactory;
use Zend\ServiceManager\ServiceManager;

$serviceManager = new \Zend\ServiceManager\ServiceManager([
    'factories' => [
        Buzzer::class             => InvokableFactory::class,
    ],
    'lazy_services' => [
         // Mapping services to their class names is required
         // since the ServiceManager is not a declarative DIC.
         'class_map' => [
             Buzzer::class => Buzzer::class,
         ],
    ],
    'delegators' => [
        Buzzer::class => [
            LazyServiceFactory::class,
        ],
    ],
]);
```

This configuration tells the service manager to add the add
`LazyServiceFactory` as a delegator for `Buzzer`.

You can now retrieve the buzzer:

```php
use MyApp\Buzzer;

$buzzer = $serviceManager->get(Buzzer::class);
echo $buzzer->buzz();
```

To verify that the proxying occurred correctly, you can run the following code,
which should delay the 5 seconds wait time hardcoded in `Buzzer::__construct`
until `Buzzer::buzz` is invoked:

```php
use MyApp\Buzzer;

for ($i = 0; $i < 100; $i += 1) {
    $buzzer = $serviceManager->get(Buzzer::class);
    echo "created buzzer $i\n";
}

echo $buzzer->buzz();
```

## Configuration

This is the config structure expected by `Zend\ServiceManager\Proxy\LazyServiceFactory`,
in the `lazy_services` key passed in the service manager configuration:

```php
[
    // map of service names and their relative class names - this
    // is required since the service manager cannot know the
    // class name of defined services up front
    'class_map' => [
        // 'foo' => 'MyApplication\Foo',
    ],

    // directory where proxy classes will be written - default to system_get_tmp_dir()
    'proxies_target_dir' => null,

    // namespace of the generated proxies, default to "ProxyManagerGeneratedProxy"
    'proxies_namespace' => null,

    // whether the generated proxy classes should be written to disk or generated on-the-fly
    'write_proxy_files' => false,
];
```

After you have an instance, you can map lazy service/class pairs using
`mapLazyService()`:

```php
$container->mapLazyService('foo', \MyApplication\Foo::class);
```
