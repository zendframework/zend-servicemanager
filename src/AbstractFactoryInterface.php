<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager;

/**
 * Backwards-compatibility shim for AbstractFactoryInterface.
 *
 * Implementations should update to implement only Zend\ServiceManager\Factory\AbstractFactoryInterface.
 *
 * If upgrading from v3, take the following steps:
 *
 * - change the typehint from `Interop\Container\ContainerInterface` to `Psr\Container\ContainerInterface`
 *
 * Once you have tested your code, you can then update your class to only implement
 * Zend\ServiceManager\Factory\AbstractFactoryInterface, and remove the `canCreateServiceWithName()`
 * and `createServiceWithName()` methods.
 *
 * @deprecated Use Zend\ServiceManager\Factory\AbstractFactoryInterface instead.
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
