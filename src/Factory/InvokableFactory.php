<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Factory;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory to create newable classes
 */
final class InvokableFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(ServiceLocatorInterface $serviceLocator, $requestedName, array $options = [])
    {
        return new $requestedName($options);
    }
}
