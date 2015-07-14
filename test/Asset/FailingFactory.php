<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 14/07/2015
 * Time: 11:30
 */

namespace ZendTest\ServiceManager\Asset;

use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FailingFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(ServiceLocatorInterface $serviceLocator, $requestedName, array $options = [])
    {
        throw new \RuntimeException('There is an error');
    }
}