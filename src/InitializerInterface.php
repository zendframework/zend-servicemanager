<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace Mxc\ServiceManager;

/**
 * Backwards-compatibility shim for InitializerInterface.
 *
 * Implementations should update to implement only Mxc\ServiceManager\Initializer\InitializerInterface.
 *
 * If upgrading from v2, take the following steps:
 *
 * - rename the method `initialize()` to `__invoke()`, and:
 *   - rename the `$serviceLocator` argument to `$container`, and change the
 *     typehint to `Interop\Container\ContainerInterface`
 *   - swap the order of the arguments (so that `$instance` comes second)
 * - create an `initialize()` method as defined in this interface, and have it
 *   proxy to `__invoke()`, passing the arguments in the new order.
 *
 * Once you have tested your code, you can then update your class to only implement
 * Mxc\ServiceManager\Initializer\InitializerInterface, and remove the `initialize()`
 * method.
 *
 * @deprecated Use Mxc\ServiceManager\Initializer\InitializerInterface instead.
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
