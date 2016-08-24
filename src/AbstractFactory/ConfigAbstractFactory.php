<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\AbstractFactory;

use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class ConfigAbstractFactory implements AbstractFactoryInterface
{

    /**
     * Can the factory create an instance for the service?
     *
     * @param  \Interop\Container\ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(\Interop\Container\ContainerInterface $container, $requestedName)
    {
        if (!$container->has('config') || !array_key_exists(self::class, $container->get('config'))) {
            return false;
        }
        $config = $container->get('config');
        $dependencies = $config[self::class];

        return array_key_exists($requestedName, $dependencies);
    }

    /**
     * Create an object
     *
     * @param  \Interop\Container\ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException if unable to resolve the service.
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws \Interop\Container\Exception\ContainerException if any other error occurs
     */
    public function __invoke(\Interop\Container\ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $dependencies = $config[self::class][$requestedName];

        // class has no dependencies, just create it and return
        if (empty($dependencies)) {
            return new $requestedName();
        }

        $arguments = [];
        foreach ($dependencies as $dependency) {
            $arguments[] = $container->get($dependency);
        }

        return new $requestedName(...$arguments);
    }
}
