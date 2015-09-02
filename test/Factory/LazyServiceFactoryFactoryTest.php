<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\LazyServiceFactoryFactory;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use ZendTest\ServiceManager\TestAsset\InvokableObject;

class LazyServiceFactoryFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionThrownWhenLazyServiceConfigMissing()
    {
        $container = $this->getMock(ContainerInterface::class);
        $factory   = new LazyServiceFactoryFactory();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Missing "lazy_services" config key'
        );

        $object = $factory($container, InvokableObject::class);
    }

    public function testExceptionThrownWhenLazyServiceConfigMissingClassMap()
    {
        $container = $this->getMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->will($this->returnValue([
                'lazy_services' => []
            ]));

        $factory = new LazyServiceFactoryFactory();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Missing "class_map" config key in "lazy_services"'
        );

        $object = $factory($container, InvokableObject::class);
    }
}
