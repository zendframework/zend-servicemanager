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
use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ServiceManager\TestAsset\InvokableObject;

/**
 * @covers \Zend\ServiceManager\Factory\InvokableFactory
 */
class InvokableFactoryTest extends TestCase
{
    public function testCanCreateObjectWhenInvokedUsingProvidedOptions()
    {
        $container = $this->getMock(ContainerInterface::class);
        $factory   = new InvokableFactory();

        $object = $factory($container, InvokableObject::class, ['foo' => 'bar']);

        $this->assertInstanceOf(InvokableObject::class, $object);
        $this->assertEquals(['foo' => 'bar'], $object->options);
    }

    public function testCanCreateObjectViaCreateServiceWhenCanonicalNameIsNormalizedNameAndRequestedNameIsQualified()
    {
        $container = new ServiceManager();
        $factory   = new InvokableFactory();

        $object = $factory->createService($container, 'invokableobject', InvokableObject::class);

        $this->assertInstanceOf(InvokableObject::class, $object);
    }

    public function testCanCreateObjectViaCreateServiceWhenCanonicalNameIsQualified()
    {
        $container = new ServiceManager();
        $factory   = new InvokableFactory();

        $object = $factory->createService($container, InvokableObject::class, 'invokableobject');

        $this->assertInstanceOf(InvokableObject::class, $object);
    }

    public function testRaisesExceptionIfNeitherCanonicalNorRequestedNameAreQualified()
    {
        $container = new ServiceManager();
        $factory   = new InvokableFactory();

        $this->setExpectedException(InvalidServiceException::class);
        $object = $factory->createService($container, 'invokableobject', 'invokableobject');
    }
}
