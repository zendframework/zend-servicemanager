<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager;

use Zend\ServiceManager\Exception;

/**
 * Interface for service locator
 */
interface ServiceLocatorInterface
{
    /**
     * Retrieve a service by its name, with optional options
     *
     * @param  string $name
     * @param  array  $options
     * @return object
     * @throws Exception\ServiceNotFoundException If no factory/abstract factory could be found to create the instance
     * @throws Exception\ServiceNotCreatedException If factory/delegator fails to create the instance
     */
    public function get($name, array $options = []);

    /**
     * Check if the service locator has a registered service for the given name
     *
     * @param  string $name
     * @return bool
     */
    public function has($name);
}
