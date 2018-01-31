<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager;

/**
 * Backwards-compatibility shim for FactoryInterface.
 *
 * Implementations should update to implement only Zend\ServiceManager\Factory\FactoryInterface.
 *
 * If upgrading from v3, take the following steps:
 *
 * - change the typehint from `Interop\Container\ContainerInterface` to `Psr\Container\ContainerInterface`.
 *
 * Once you have tested your code, you can then update your class to only implement
 * Zend\ServiceManager\Factory\FactoryInterface, and remove the `createService()`
 * method.
 *
 * @deprecated Use Zend\ServiceManager\Factory\FactoryInterface instead.
 */
interface FactoryInterface extends Factory\FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator);
}
