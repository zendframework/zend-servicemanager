<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\AbstractFactory;

use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

final class ConfigAbstractFactory implements AbstractFactoryInterface
{

    /**
     * Factory can create the service if there is a key for it in the config
     *
     * {@inheritdoc}
     */
    public function canCreate(\Interop\Container\ContainerInterface $container, $requestedName)
    {
        if (!$container->has('config') || !array_key_exists(self::class, $container->get('config'))) {
            return false;
        }
        $config = $container->get('config');
        $dependencies = $config[self::class];

        // config must be array, and have a key for the requested name that's value is also an array
        if (!is_array($dependencies)
            || !array_key_exists($requestedName, $dependencies)
            || !is_array($dependencies[$requestedName])
        ) {
            return false;
        }

        // we can only create this service if the config is an array of strings
        return $dependencies[$requestedName] === array_values(array_map('strval', $dependencies[$requestedName]));

    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(\Interop\Container\ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!$container->has('config')) {
            throw new ServiceNotCreatedException('Cannot find a config array in the container');
        }

        $config = $container->get('config');

        if (!is_array($config)) {
            throw new ServiceNotCreatedException('Config must be an array');
        }

        if (!array_key_exists(self::class, $config)) {
            throw new ServiceNotCreatedException('Cannot find a `' . self::class . '` key in the config array');
        }

        $dependencies = $config[self::class];

        if (!is_array($dependencies)
            || !array_key_exists($requestedName, $dependencies)
            || !is_array($dependencies[$requestedName])
            || $dependencies[$requestedName] !== array_values(array_map('strval', $dependencies[$requestedName]))
        ) {
            throw new ServiceNotCreatedException('Dependencies config must exist and be an array of strings');
        }
        $dependencies = $dependencies[$requestedName];
        $arguments = array_map([$container, 'get'], $dependencies);

        return new $requestedName(...$arguments);
    }
}
