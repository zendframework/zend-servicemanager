<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use DateTime;
use stdClass;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Initializer\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ServiceManager\TestAsset\FailingFactory;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\SimpleAbstractFactory;
use ZendTest\ServiceManager\TestAsset\SimpleServiceManager;

class ServiceManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testIsSharedByDefault()
    {
        $serviceManager = new ServiceManager([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ]
        ]);

        $object1 = $serviceManager->get(stdClass::class);
        $object2 = $serviceManager->get(stdClass::class);

        $this->assertSame($object1, $object2);
    }

    public function testCanDisableSharedByDefault()
    {
        $serviceManager = new ServiceManager([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ],
            'shared_by_default' => false
        ]);

        $object1 = $serviceManager->get(stdClass::class);
        $object2 = $serviceManager->get(stdClass::class);

        $this->assertNotSame($object1, $object2);
    }

    public function testCanDisableSharedForSingleService()
    {
        $serviceManager = new ServiceManager([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ],
            'shared' => [
                stdClass::class => false
            ]
        ]);

        $object1 = $serviceManager->get(stdClass::class);
        $object2 = $serviceManager->get(stdClass::class);

        $this->assertNotSame($object1, $object2);
    }

    public function testCanEnableSharedForSingleService()
    {
        $serviceManager = new ServiceManager([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ],
            'shared_by_default' => false,
            'shared'            => [
                stdClass::class => true
            ]
        ]);

        $object1 = $serviceManager->get(stdClass::class);
        $object2 = $serviceManager->get(stdClass::class);

        $this->assertSame($object1, $object2);
    }

    public function testCanCreateObjectWithInvokableFactory()
    {
        $serviceManager = new ServiceManager([
            'factories' => [
                InvokableObject::class => InvokableFactory::class
            ]
        ]);

        $object = $serviceManager->get(InvokableObject::class, ['foo' => 'bar']);

        $this->assertInstanceOf(InvokableObject::class, $object);
        $this->assertEquals(['foo' => 'bar'], $object->options);
    }

    public function testCanCreateObjectWithClosureFactory()
    {
        $serviceManager = new ServiceManager([
            'factories' => [
                stdClass::class => function (ServiceLocatorInterface $serviceLocator, $className) {
                    $this->assertEquals(stdClass::class, $className);
                    return new stdClass();
                }
            ]
        ]);

        $object = $serviceManager->get(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $object);
    }

    public function testCanCreateServiceWithAbstractFactory()
    {
        $serviceManager = new ServiceManager([
            'abstract_factories' => [
                new SimpleAbstractFactory()
            ]
        ]);

        $serviceManager->get(DateTime::class);
    }

    public function testCanCreateServiceWithAlias()
    {
        $serviceManager = new ServiceManager([
            'factories' => [
                InvokableObject::class => InvokableFactory::class
            ],
            'aliases' => [
                'foo' => InvokableObject::class,
                'bar' => 'foo'
            ]
        ]);

        $object = $serviceManager->get('bar');

        $this->assertInstanceOf(InvokableObject::class, $object);
        $this->assertTrue($serviceManager->has('bar'));
        $this->assertFalse($serviceManager->has('baz'));
    }

    public function testCanCheckServiceExistenceWithoutCheckingAbstractFactories()
    {
        $serviceManager = new ServiceManager([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ]
        ]);

        $this->assertTrue($serviceManager->has(stdClass::class));
        $this->assertFalse($serviceManager->has(DateTime::class));
    }

    public function testCanCheckServiceExistenceWithCheckingAbstractFactories()
    {
        $serviceManager = new ServiceManager([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ],
            'abstract_factories' => [
                new SimpleAbstractFactory() // This one always return true
            ]
        ]);

        $this->assertTrue($serviceManager->has(stdClass::class, true));
        $this->assertTrue($serviceManager->has(DateTime::class, true));
    }

    public function testNeverShareIfOptionsArePassed()
    {
        $serviceManager = new ServiceManager([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ],
            'shared' => [
                stdClass::class => true
            ]
        ]);

        $object1 = $serviceManager->get(stdClass::class);
        $object2 = $serviceManager->get(stdClass::class, ['foo' => 'bar']);

        $this->assertNotSame($object1, $object2);
    }

    public function testInitializersAreRunAfterCreation()
    {
        $initializer = $this->getMock(InitializerInterface::class);

        $serviceManager = new ServiceManager([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ],
            'initializers' => [
                $initializer
            ]
        ]);

        $initializer->expects($this->once())
                    ->method('__invoke')
                    ->with($serviceManager, $this->isInstanceOf(stdClass::class));

        // We call it twice to make sure that the initializer is only called once

        $serviceManager->get(stdClass::class);
        $serviceManager->get(stdClass::class);
    }

    public function testThrowExceptionIfServiceCannotBeCreated()
    {
        $serviceManager = new ServiceManager([
            'factories' => [
                stdClass::class => FailingFactory::class
            ]
        ]);

        $this->setExpectedException(ServiceNotCreatedException::class);

        $serviceManager->get(stdClass::class);
    }

    public function testConfigurationCanBeMerged()
    {
        $serviceManager = new SimpleServiceManager([
            'factories' => [
                DateTime::class => InvokableFactory::class
            ]
        ]);

        $this->assertTrue($serviceManager->has(DateTime::class));
        $this->assertTrue($serviceManager->has(stdClass::class));
    }

    public function testConfigurationTakesPrecedenceWhenMerged()
    {
        $factory = $this->getMock(FactoryInterface::class);

        $factory->expects($this->once())->method('__invoke');

        $serviceManager = new SimpleServiceManager([
            'factories' => [
                stdClass::class => $factory
            ]
        ]);

        $serviceManager->get(stdClass::class);
    }
}
