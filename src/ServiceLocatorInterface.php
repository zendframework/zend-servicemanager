<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace Mxc\ServiceManager;

use Interop\Container\ContainerInterface as InteropContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Interface for service locator
 */
interface ServiceLocatorInterface extends
    PsrContainerInterface,
    InteropContainerInterface
{
    /**
     * Build a service by its name, using optional options (such services are NEVER cached).
     *
     * @param  string $name
     * @param  null|array  $options
     * @return mixed
     * @throws Exception\ServiceNotFoundException If no factory/abstract
     *     factory could be found to create the instance.
     * @throws Exception\ServiceNotCreatedException If factory/delegator fails
     *     to create the instance.
     * @throws ContainerExceptionInterface if any other error occurs
     */
    public function build($name, array $options = null);
}
