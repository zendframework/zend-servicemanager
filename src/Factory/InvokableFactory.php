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
     * First, it checks if `$canonicalName` resolves to a class, and, if so, uses
     * that value to proxy to `__invoke()`.
     *
     * Next, if `$requestedName` is non-empty and resolves to a class, this
     * method uses that value to proxy to `__invoke()`.
     *
     * Finally, if the above each fail, it raises an exception.
     *
     * The approach above is perfomed as version 2 has two distinct behaviors
     * under which factories are invoked:
     *
     * - If an alias was used, $canonicalName is the resolved name, and
     *   $requestedName is the service name requested;
     * - Otherwise, $canonicalName is the normalized name, and $requestedName
     *   is the original service name requested (typically a class name).
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param null|string $canonicalName
     * @param null|string $requestedName
     * @return object
     * @throws InvalidServiceNameException
     */
    public function createService(ServiceLocatorInterface $serviceLocator, $canonicalName = null, $requestedName = null)
    {
        if (class_exists($canonicalName)) {
            return $this($serviceLocator, $canonicalName);
        }

        if (is_string($requestedName) && class_exists($requestedName)) {
            return $this($serviceLocator, $requestedName);
        }

        throw new InvalidServiceNameException(sprintf(
            '%s requires that the requested name is provided on invocation; please update your tests or consuming container',
            __CLASS__
        ));
    }
}
