<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace Mxc\ServiceManager;

/**
 * Backwards-compatibility shim for AbstractFactoryInterface.
 *
 * Implementations should update to implement only Mxc\ServiceManager\Factory\AbstractFactoryInterface.
 *
 * If upgrading from v2, take the following steps:
 *
 * - rename the method `canCreateServiceWithName()` to `canCreate()`, and:
 *   - rename the `$serviceLocator` argument to `$container`, and change the
 *     typehint to `Interop\Container\ContainerInterface`
 *   - merge the `$name` and `$requestedName` arguments
 * - rename the method `createServiceWithName()` to `__invoke()`, and:
 *   - rename the `$serviceLocator` argument to `$container`, and change the
 *     typehint to `Interop\Container\ContainerInterface`
 *   - merge the `$name` and `$requestedName` arguments
 *   - add the optional `array $options = null` argument.
 * - create a `canCreateServiceWithName()` method as defined in this interface, and have it
 *   proxy to `canCreate()`, passing `$requestedName` as the second argument.
 * - create a `createServiceWithName()` method as defined in this interface, and have it
 *   proxy to `__invoke()`, passing `$requestedName` as the second argument.
 *
 * Once you have tested your code, you can then update your class to only implement
 * Mxc\ServiceManager\Factory\AbstractFactoryInterface, and remove the `canCreateServiceWithName()`
 * and `createServiceWithName()` methods.
 *
 * @deprecated Use Mxc\ServiceManager\Factory\AbstractFactoryInterface instead.
 */
interface AbstractFactoryInterface extends Factory\AbstractFactoryInterface
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
