# Quick Start

Zend Framework Service Manager components is a modern, fast and easy-to-use implementation of the 
[Service Locator design pattern](https://en.wikipedia.org/wiki/Service_locator_pattern). The implementation
implements the [Container Interop](https://github.com/container-interop/container-interop) interfaces, for increased
interoperability.

You can start with the simplest example by following those steps.

## 1. Install Zend Service Manager

If you haven't already, [install Composer](https://getcomposer.org). Once you have, you can install
service manager:

```bash
$ composer require zendframework/zend-servicemanager
```

## 2. Configuring a service manager

You can now create and configure a service manager. The service manager constructor accepts a simple array:

```php
<?php

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Factory\InvokableFactory;
use stdClass;

$serviceManager = new ServiceManager([
    'factories' => [
        stdClass::class => InvokableFactory::class
    ]
]);
```

The service manager accepts a lot of different keys, that are explained in the `Configuring service manager` section.

## 3. Retrieving objects

Finally, you can create objects using the `get` method:

```php
$object = $serviceManager->get(stdClass::class);
```

By default, all objects created through the service manager are shared. This means that calling the `get` method
twice will return the exact same object:

```php
$object1 = $serviceManager->get(stdClass::class);
$object2 = $serviceManager->get(stdClass::class);

var_dump($object1 === $object2); // prints "true"
```

You can use the new `build` method, that never caches instances:

```php
$object1 = $serviceManager->build(stdClass::class);
$object2 = $serviceManager->build(stdClass::class);

var_dump($object1 === $object2); // prints "true"
```