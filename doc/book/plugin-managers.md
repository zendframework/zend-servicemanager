# Plugin managers

Plugin managers are *specialized* service managers. In other words, you could use plugin managers to create
homogeneous objects, instead of relying on the global service manager. Because a plugin manager extends a service
manager, it works the same and can be configured similarly. However it provides a better separation of concerns and
can provide additional security checks.

Zend Framework components extensively use plugin managers to create services that share common functionalities. For instance,
all validators services are specified inside a specialized `ValidatorPluginManager`.

## Creating a plugin manager

To create a plugin manager, you first need to create a new class that extends the `Zend\ServiceManager\AbstractPluginManager`
class:

```php
class ValidatorPluginManager extends AbstractPluginManager
{
    protected $instanceOf = ValidatorInterface::class;
}
```

The `$instanceOf` variable is a specific variable that you could specify to provide enhanced checks over the built
instances. In other words, whenever the `ValidatorPluginManager` will create an instance, it will check if the created
instance is an instance of `$instanceOf`. If that's not the case, a `Zend\ServiceManager\Exception\InvalidServiceException`
exception will be thrown.

Most of the time, this shortcut is enough. However if you have more complex validation rules, you can override the
`validate` method:

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

Then, you can create a plugin manager. A plugin manager requires that you pass a parent service manager (typically, the
application's service manager) as well as a configuration. This configuration follows the exact same pattern as for a
normal service manager, so please refer to the "configuring the service manager" section.

Because a plugin manager is often a service itself, we recommend you to register the plugin manager as part of the
general service manager, as shown below:

```php
$serviceManager = new ServiceManager([
    'factories' => [
        ValidatorPluginManager::class => function(ContainerInterface $container, $requestedName) {
            return new ValidatorPluginManager($container, [
                'factories' => [
                    StringLengthValidator::class => InvokableFactory::class
                ]
            ]);
        }
    ]
]);

// Get the plugin manager:

$pluginManager = $serviceManager->get(ValidatorPluginManager::class);

// Use the plugin manager

$validator = $pluginManager->get(StringLengthValidator::class);
```

> Contrary to Zend Framework 2 implementation, when inside the context of the factory of a service created by a plugin
manager, the passed container **will not be** the plugin manager, but the parent service manager instead.