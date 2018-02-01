<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager;

/**
 * Backwards-compatibility shim for InitializerInterface.
 *
 * Implementations should update to implement only Zend\ServiceManager\Initializer\InitializerInterface.
 *
 * If upgrading from v3, take the following steps:
 *
 * - change the typehint from `Interop\Container\ContainerInterface` to `Psr\Container\ContainerInterface`.
 *
 * Once you have tested your code, you can then update your class to only implement
 * Zend\ServiceManager\Initializer\InitializerInterface, and remove the `initialize()`
 * method.
 *
 * @deprecated Use Zend\ServiceManager\Initializer\InitializerInterface instead.
 */
interface InitializerInterface extends Initializer\InitializerInterface
{
    /**
     * Initialize
     *
     * @param $instance
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function initialize($instance, ServiceLocatorInterface $serviceLocator);
}
