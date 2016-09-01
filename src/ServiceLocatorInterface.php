<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;

/**
 * Interface for service locator
 */
interface ServiceLocatorInterface extends ContainerInterface
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
     * @throws ContainerException if any other error occurs
     */
    public function build($name, array $options = null);
}
