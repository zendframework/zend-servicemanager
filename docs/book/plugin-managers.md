# Plugin managers

Plugin managers are *specialized* service managers, typically used to create
homogeneous objects of a specific type.

Because a plugin manager extends a service manager, it works the same and can
be configured similarly. It provides a separation of concerns (it will be used
in specific contexts), and provides additional instance validation.

Zend Framework components extensively use plugin managers to create services
that share common functionalities. For instance, all validator services are
specified inside a specialized `ValidatorPluginManager`.

## Creating a plugin manager

To create a plugin manager, you first need to create a new class that extends
`Zend\ServiceManager\AbstractPluginManager`:

```php
class ValidatorPluginManager extends AbstractPluginManager
{
    protected $instanceOf = ValidatorInterface::class;
}
```

The `$instanceOf` variable specifies a class/interface type that all instances
retrieved from the plugin manager must fulfill. If an instance created by the
plugin manager does not match, a `Zend\ServiceManager\Exception\InvalidServiceException`
exception will be thrown.

Most of the time, this shortcut is enough. However if you have more complex
validation rules, you can override the `validate()` method:

```php
class ValidatorPluginManager extends AbstractPluginManager
{
    public function validate($instance)
    {
        if ($instance instanceof Foo || $instance instanceof Bar) {
            return;
        }
    
        throw new InvalidServiceException('This is not a valid service!');
    }
}
```

## Configuring a plugin manager

A plugin manager requires that you pass a parent service manager (typically,
the application's service manager) as well as service configuration. Service
configuration follows the exact same pattern as for a normal service manager;
refer to the [configuring the service manager](configuring-the-service-manager.md) section for details.

Because a plugin manager is often a service itself, we recommend you to
register the plugin manager as part of the general service manager, as shown
below:

```php
$serviceManager = new ServiceManager([
    'factories' => [
        ValidatorPluginManager::class => function(ContainerInterface $container, $requestedName) {
            return new ValidatorPluginManager($container, [
                'factories' => [
                    StringLengthValidator::class => InvokableFactory::class,
                ],
            ]);
        },
    ],
]);

// Get the plugin manager:

$pluginManager = $serviceManager->get(ValidatorPluginManager::class);

// Use the plugin manager

$validator = $pluginManager->get(StringLengthValidator::class);
```

> Unlike the version 2 implementation, when inside the context of the factory
> of a service created by a plugin manager, the passed container **will not
> be** the plugin manager, but the parent service manager instead. If you need
> access to other plugins of the same type, you will need to fetch the plugin
> manager from the container:
>
> ```php
> function ($container, $name, array $options = []) {
>     $validators = $container->get(ValidatorPluginManager::class);
>     // ...
> }
> ```
