<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\InvalidServiceNameException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for instantiating classes with no dependencies or which accept a single array.
 *
 * The InvokableFactory can be used for any class that:
 *
 * - has no constructor arguments;
 * - accepts a single array of arguments via the constructor.
 *
 * It replaces the "invokables" and "invokable class" functionality of the v2
 * service manager, and can also be used in v2 code for forwards compatibility
 * with v3.
 */
final class InvokableFactory implements FactoryInterface
{
    /**
     * Create an instance of the requested class name.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return (null === $options) ? new $requestedName : new $requestedName($options);
    }

    /**
     * Create an instance of the named service.
     *
     * If `$requestedName` is not provided, raises an exception; otherwise,
     * proxies to the `__invoke()` method to create an instance of the
     * requested class.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param null|string $canonicalName Ignored
     * @param null|string $requestedName
     * @return object
     * @throws InvalidServiceNameException
     */
    public function createService(ServiceLocatorInterface $serviceLocator, $canonicalName = null, $requestedName = null)
    {
        if (! $requestedName) {
            throw new InvalidServiceNameException(sprintf(
                '%s requires that the requested name is provided on invocation; please update your tests or consuming container',
                __CLASS__
            ));
        }
        return $this($serviceLocator, $requestedName);
    }
}
