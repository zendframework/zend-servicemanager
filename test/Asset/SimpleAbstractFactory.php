<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Asset;

use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SimpleAbstractFactory implements AbstractFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function canCreateServiceWithName($name)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(ServiceLocatorInterface $serviceLocator, $className, array $options = [])
    {
        if (empty($options)) {
            return new $className();
        }

        return new $className($options);
    }
}