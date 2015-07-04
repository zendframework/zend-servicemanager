<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Factory;

use Zend\ServiceManager\Factory\LazyServiceFactoryFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use ZendTest\ServiceManager\Asset\InvokableObject;

class LazyServiceFactoryFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionThrownWhenLazyServiceConfigMissing()
    {
        $serviceLocator = $this->getMock(ServiceLocatorInterface::class);
        $factory        = new LazyServiceFactoryFactory();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Missing "lazy_services" config key'
        );

        $object = $factory($serviceLocator, InvokableObject::class);
    }

    public function testExceptionThrownWhenLazyServiceConfigMissingClassMap()
    {
        $serviceLocator = $this->getMock(ServiceLocatorInterface::class);
        $serviceLocator->expects($this->once())
            ->method('get')
            ->with('Config')
            ->will($this->returnValue([
                'lazy_services' => []
            ]));

        $factory = new LazyServiceFactoryFactory();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Missing "class_map" config key in "lazy_services"'
        );

        $object = $factory($serviceLocator, InvokableObject::class);
    }
}
