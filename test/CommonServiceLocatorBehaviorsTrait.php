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
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Initializer\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use ZendTest\ServiceManager\TestAsset\FailingAbstractFactory;
use ZendTest\ServiceManager\TestAsset\FailingFactory;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\SimpleAbstractFactory;

trait CommonServiceLocatorBehaviorsTrait
{
    /**
     * The creation context container; used in some mocks for comparisons; set during createContainer.
     */
    protected $creationContext;

    abstract public function createContainer(array $config = []);

    public function testIsSharedByDefault()
    {
        $serviceManager = $this->createContainer([
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
        $serviceManager = $this->createContainer([
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
        $serviceManager = $this->createContainer([
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
        $serviceManager = $this->createContainer([
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

    public function testCanBuildObjectWithInvokableFactory()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                InvokableObject::class => InvokableFactory::class
            ]
        ]);

        $object = $serviceManager->build(InvokableObject::class, ['foo' => 'bar']);

        $this->assertInstanceOf(InvokableObject::class, $object);
        $this->assertEquals(['foo' => 'bar'], $object->options);
    }

    public function testCanCreateObjectWithClosureFactory()
    {
        $serviceManager = $this->createContainer([
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
        $serviceManager = $this->createContainer([
            'abstract_factories' => [
                new SimpleAbstractFactory()
            ]
        ]);

        $serviceManager->get(DateTime::class);
    }

    public function testCanCreateServiceWithAlias()
    {
        $serviceManager = $this->createContainer([
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
        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ]
        ]);

        $this->assertTrue($serviceManager->has(stdClass::class));
        $this->assertFalse($serviceManager->has(DateTime::class));
    }

    public function testCanCheckServiceExistenceWithCheckingAbstractFactories()
    {
        $serviceManager = $this->createContainer([
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

    public function testBuildNeverSharesInstances()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ],
            'shared' => [
                stdClass::class => true
            ]
        ]);

        $object1 = $serviceManager->build(stdClass::class);
        $object2 = $serviceManager->build(stdClass::class, ['foo' => 'bar']);

        $this->assertNotSame($object1, $object2);
    }

    public function testInitializersAreRunAfterCreation()
    {
        $initializer = $this->getMock(InitializerInterface::class);

        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ],
            'initializers' => [
                $initializer
            ]
        ]);

        $initializer->expects($this->once())
                    ->method('__invoke')
                    ->with($this->creationContext, $this->isInstanceOf(stdClass::class));

        // We call it twice to make sure that the initializer is only called once

        $serviceManager->get(stdClass::class);
        $serviceManager->get(stdClass::class);
    }

    public function testThrowExceptionIfServiceCannotBeCreated()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => FailingFactory::class
            ]
        ]);

        $this->setExpectedException(ServiceNotCreatedException::class);

        $serviceManager->get(stdClass::class);
    }

    public function testCanCreateNewLocatorWithMergedConfig()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                DateTime::class => InvokableFactory::class
            ]
        ]);

        $newServiceManager = $serviceManager->withConfig([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ]
        ]);

        $this->assertTrue($serviceManager->has(DateTime::class));
        $this->assertFalse($serviceManager->has(stdClass::class));

        $this->assertTrue($newServiceManager->has(DateTime::class));
        $this->assertTrue($newServiceManager->has(stdClass::class));

        // Make sure the context has been updated for the new container

        $reflectionProperty = new \ReflectionProperty($newServiceManager, 'creationContext');
        $reflectionProperty->setAccessible(true);

        $this->assertSame($newServiceManager, $reflectionProperty->getValue($newServiceManager));
    }

    public function testOverrideConfigWhenMerged()
    {
        $firstFactory  = $this->getMock(FactoryInterface::class);
        $secondFactory = $this->getMock(FactoryInterface::class);

        $serviceManager = $this->createContainer([
            'factories' => [
                DateTime::class => $firstFactory
            ]
        ]);

        $newServiceManager = $serviceManager->withConfig([
            'factories' => [
                DateTime::class => $secondFactory
            ]
        ]);

        $firstFactory->expects($this->never())->method('__invoke');
        $secondFactory->expects($this->once())->method('__invoke');

        $newServiceManager->get(DateTime::class);
    }

    /**
     * @group has
     */
    public function testHasReturnsFalseIfServiceNotConfigured()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => InvokableFactory::class,
            ],
        ]);
        $this->assertFalse($serviceManager->has('Some\Made\Up\Entry'));
    }

    /**
     * @group has
     */
    public function testHasReturnsTrueIfServiceIsConfigured()
    {
        $serviceManager = $this->createContainer([
            'services' => [
                stdClass::class => new stdClass,
            ],
        ]);
        $this->assertTrue($serviceManager->has(stdClass::class));
    }

    /**
     * @group has
     */
    public function testHasReturnsTrueIfFactoryIsConfigured()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => InvokableFactory::class,
            ],
        ]);
        $this->assertTrue($serviceManager->has(stdClass::class));
    }

    /**
     * @group has
     */
    public function testHasDoesNotCheckAbstractFactoriesByDefault()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => InvokableFactory::class,
            ],
            'abstract_factories' => [
                new SimpleAbstractFactory(),
            ],
        ]);

        $this->assertFalse($serviceManager->has(DateTime::class));
    }

    public function abstractFactories()
    {
        return [
            'simple'  => [new SimpleAbstractFactory(), 'assertTrue'],
            'failing' => [new FailingAbstractFactory(), 'assertFalse'],
        ];
    }

    /**
     * @group has
     * @dataProvider abstractFactories
     */
    public function testHasCanCheckAbstractFactoriesWhenRequested($abstractFactory, $assertion)
    {
        $serviceManager = $this->createContainer([
            'abstract_factories' => [
                $abstractFactory,
            ],
        ]);

        $this->{$assertion}($serviceManager->has(DateTime::class, true));
    }

    /**
     * @covers \Zend\ServiceManager\ServiceManager::configure
     */
    public function testCanConfigureAllServiceTypes()
    {
        $serviceManager = $this->createContainer([
            'services' => [
                'config' => ['foo' => 'bar'],
            ],
            'factories' => [
                stdClass::class => InvokableFactory::class,
            ],
            'delegators' => [
                stdClass::class => [
                    function ($container, $name, $callback) {
                        $instance = $callback();
                        $instance->foo = 'bar';
                        return $instance;
                    },
                ],
            ],
            'shared' => [
                'config'        => true,
                stdClass::class => true,
            ],
            'aliases' => [
                'Aliased' => stdClass::class,
            ],
            'shared_by_default' => false,
            'abstract_factories' => [
                new SimpleAbstractFactory(),
            ],
            'initializers' => [
                function ($container, $instance) {
                    if (! $instance instanceof stdClass) {
                        return;
                    }
                    $instance->bar = 'baz';
                },
            ],
        ]);

        $dateTime = $serviceManager->get(DateTime::class);
        $this->assertInstanceOf(DateTime::class, $dateTime);
        $notShared = $serviceManager->get(DateTime::class);
        $this->assertInstanceOf(DateTime::class, $notShared);
        $this->assertNotSame($dateTime, $notShared);

        $config = $serviceManager->get('config');
        $this->assertInternalType('array', $config);
        $this->assertSame($config, $serviceManager->get('config'));

        $stdClass = $serviceManager->get(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $stdClass);
        $this->assertSame($stdClass, $serviceManager->get(stdClass::class));
        $this->assertEquals('bar', $stdClass->foo);
        $this->assertEquals('baz', $stdClass->bar);
    }

    /**
     * @covers \Zend\ServiceManager\ServiceManager::configure
     */
    public function testCanSpecifyAbstractFactoryUsingStringViaConfiguration()
    {
        $serviceManager = $this->createContainer([
            'abstract_factories' => [
                SimpleAbstractFactory::class,
            ],
        ]);

        $dateTime = $serviceManager->get(DateTime::class);
        $this->assertInstanceOf(DateTime::class, $dateTime);
    }

    public function invalidFactories()
    {
        return [
            'null'                 => [null],
            'true'                 => [true],
            'false'                => [false],
            'zero'                 => [0],
            'int'                  => [1],
            'zero-float'           => [0.0],
            'float'                => [1.1],
            'array'                => [['foo', 'bar']],
            'non-invokable-object' => [(object) ['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider invalidFactories
     * @covers \Zend\ServiceManager\ServiceManager::configure
     */
    public function testPassingInvalidAbstractFactoryTypeViaConfigurationRaisesException($factory)
    {
        $this->setExpectedException(InvalidArgumentException::class, 'invalid abstract factory');
        $serviceManager = $this->createContainer([
            'abstract_factories' => [
                $factory,
            ],
        ]);
    }

    public function testCanSpecifyInitializerUsingStringViaConfiguration()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => InvokableFactory::class,
            ],
            'initializers' => [
                TestAsset\SimpleInitializer::class,
            ],
        ]);

        $instance = $serviceManager->get(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $instance);
        $this->assertEquals('bar', $instance->foo);
    }

    /**
     * @dataProvider invalidFactories
     * @covers \Zend\ServiceManager\ServiceManager::configure
     */
    public function testPassingInvalidInitializerTypeViaConfigurationRaisesException($initializer)
    {
        $this->setExpectedException(InvalidArgumentException::class, 'invalid initializer');
        $serviceManager = $this->createContainer([
            'initializers' => [
                $initializer,
            ],
        ]);
    }

    /**
     * @covers \Zend\ServiceManager\ServiceManager::getFactory
     */
    public function testGetRaisesExceptionWhenNoFactoryIsResolved()
    {
        $serviceManager = $this->createContainer();
        $this->setExpectedException(ServiceNotCreatedException::class, 'invalid or missing factory');
        $serviceManager->get('Some\Unknown\Service');
    }

    public function invalidDelegators()
    {
        $invalidDelegators = $this->invalidFactories();
        $invalidDelegators['invalid-classname']   = ['not-a-class-name', 'invalid delegator'];
        $invalidDelegators['non-invokable-class'] = [stdClass::class];
        return $invalidDelegators;
    }

    /**
     * @dataProvider invalidDelegators
     * @covers \Zend\ServiceManager\ServiceManager::createDelegatorFromName
     */
    public function testInvalidDelegatorShouldRaiseExceptionDuringCreation($delegator, $contains = 'non-callable delegator')
    {
        $config = [
            'option' => 'OPTIONED',
        ];
        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => InvokableFactory::class,
            ],
            'delegators' => [
                stdClass::class => [
                    $delegator,
                ],
            ],
        ]);

        $this->setExpectedException(ServiceNotCreatedException::class, $contains);
        $serviceManager->get(stdClass::class);
    }
}
