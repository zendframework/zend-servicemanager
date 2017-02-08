<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use DateTime;
use Interop\Container\Exception\ContainerException;
use ReflectionProperty;
use stdClass;
use Zend\ServiceManager\Exception\ContainerModificationsNotAllowedException;
use Zend\ServiceManager\Exception\CyclicAliasException;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\Initializer\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendTest\ServiceManager\TestAsset\CallTimesAbstractFactory;
use ZendTest\ServiceManager\TestAsset\FailingAbstractFactory;
use ZendTest\ServiceManager\TestAsset\FailingFactory;
use ZendTest\ServiceManager\TestAsset\FailingExceptionWithStringAsCodeFactory;
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

        $this->assertInstanceOf(DateTime::class, $serviceManager->get(DateTime::class));
    }

    public function testAllowsMultipleInstancesOfTheSameAbstractFactory()
    {
        CallTimesAbstractFactory::setCallTimes(0);

        $obj1 = new CallTimesAbstractFactory();
        $obj2 = new CallTimesAbstractFactory();

        $serviceManager = $this->createContainer([
            'abstract_factories' => [
                $obj1,
                $obj2,
            ]
        ]);
        $serviceManager->addAbstractFactory($obj1);
        $serviceManager->addAbstractFactory($obj2);
        $serviceManager->has(stdClass::class);

        $this->assertEquals(2, CallTimesAbstractFactory::getCallTimes());
    }

    public function testWillReUseAnExistingNamedAbstractFactoryInstance()
    {
        CallTimesAbstractFactory::setCallTimes(0);

        $serviceManager = $this->createContainer([
            'abstract_factories' => [
                CallTimesAbstractFactory::class,
                CallTimesAbstractFactory::class,
            ]
        ]);
        $serviceManager->addAbstractFactory(CallTimesAbstractFactory::class);
        $serviceManager->has(stdClass::class);

        $this->assertEquals(1, CallTimesAbstractFactory::getCallTimes());
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

    public function testCheckingServiceExistenceWithChecksAgainstAbstractFactories()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ],
            'abstract_factories' => [
                new SimpleAbstractFactory() // This one always return true
            ]
        ]);

        $this->assertTrue($serviceManager->has(stdClass::class));
        $this->assertTrue($serviceManager->has(DateTime::class));
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
        $initializer = $this->getMockBuilder(InitializerInterface::class)
            ->getMock();

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

        $this->expectException(ServiceNotCreatedException::class);

        $serviceManager->get(stdClass::class);
    }

    public function testThrowExceptionWithStringAsCodeIfServiceCannotBeCreated()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => FailingExceptionWithStringAsCodeFactory::class
            ]
        ]);

        $this->expectException(ServiceNotCreatedException::class);

        $serviceManager->get(stdClass::class);
    }

    public function testConfigureCanAddNewServices()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                DateTime::class => InvokableFactory::class
            ]
        ]);

        $this->assertTrue($serviceManager->has(DateTime::class));
        $this->assertFalse($serviceManager->has(stdClass::class));

        $newServiceManager = $serviceManager->configure([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ]
        ]);

        $this->assertSame($serviceManager, $newServiceManager);

        $this->assertTrue($newServiceManager->has(DateTime::class));
        $this->assertTrue($newServiceManager->has(stdClass::class));
    }

    public function testConfigureCanOverridePreviousSettings()
    {
        $firstFactory  = $this->getMockBuilder(FactoryInterface::class)
            ->getMock();
        $secondFactory = $this->getMockBuilder(FactoryInterface::class)
            ->getMock();

        $serviceManager = $this->createContainer([
            'factories' => [
                DateTime::class => $firstFactory
            ]
        ]);

        $newServiceManager = $serviceManager->configure([
            'factories' => [
                DateTime::class => $secondFactory
            ]
        ]);

        $this->assertSame($serviceManager, $newServiceManager);

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

    public function abstractFactories()
    {
        return [
            'simple'  => [new SimpleAbstractFactory(), true],
            'failing' => [new FailingAbstractFactory(), false],
        ];
    }

    /**
     * @group has
     * @dataProvider abstractFactories
     */
    public function testHasChecksAgainstAbstractFactories($abstractFactory, $expected)
    {
        $serviceManager = $this->createContainer([
            'abstract_factories' => [
                $abstractFactory,
            ],
        ]);

        $this->assertSame($expected, $serviceManager->has(DateTime::class));
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
        $this->assertInstanceOf(DateTime::class, $dateTime, 'DateTime service did not resolve as expected');
        $notShared = $serviceManager->get(DateTime::class);
        $this->assertInstanceOf(DateTime::class, $notShared, 'DateTime service did not re-resolve as expected');
        $this->assertNotSame(
            $dateTime,
            $notShared,
            'Expected unshared instances for DateTime service but received shared instances'
        );

        $config = $serviceManager->get('config');
        $this->assertInternalType('array', $config, 'Config service did not resolve as expected');
        $this->assertSame(
            $config,
            $serviceManager->get('config'),
            'Config service resolved as unshared instead of shared'
        );

        $stdClass = $serviceManager->get(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $stdClass, 'stdClass service did not resolve as expected');
        $this->assertSame(
            $stdClass,
            $serviceManager->get(stdClass::class),
            'stdClass service should be shared, but resolved as unshared'
        );
        $this->assertTrue(
            isset($stdClass->foo),
            'Expected delegator to inject "foo" property in stdClass service, but it was not'
        );
        $this->assertEquals('bar', $stdClass->foo, 'stdClass "foo" property was not injected correctly');
        $this->assertTrue(
            isset($stdClass->bar),
            'Expected initializer to inject "bar" property in stdClass service, but it was not'
        );
        $this->assertEquals('baz', $stdClass->bar, 'stdClass "bar" property was not injected correctly');
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

    public function invalidAbstractFactories()
    {
        $factories = $this->invalidFactories();
        $factories['non-class-string'] = ['non-callable-string', 'valid class name'];
        return $factories;
    }

    /**
     * @dataProvider invalidAbstractFactories
     * @covers \Zend\ServiceManager\ServiceManager::configure
     */
    public function testPassingInvalidAbstractFactoryTypeViaConfigurationRaisesException(
        $factory,
        $contains = 'invalid abstract factory'
    ) {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($contains);
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
        $this->assertTrue(isset($instance->foo), '"foo" property was not injected by initializer');
        $this->assertEquals('bar', $instance->foo, '"foo" property was not properly injected');
    }

    public function invalidInitializers()
    {
        $factories = $this->invalidFactories();
        $factories['non-class-string'] = ['non-callable-string', 'valid function name or class name'];
        return $factories;
    }

    /**
     * @dataProvider invalidInitializers
     * @covers \Zend\ServiceManager\ServiceManager::configure
     */
    public function testPassingInvalidInitializerTypeViaConfigurationRaisesException(
        $initializer,
        $contains = 'invalid initializer'
    ) {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($contains);
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
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Unable to resolve');
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
    public function testInvalidDelegatorShouldRaiseExceptionDuringCreation(
        $delegator,
        $contains = 'non-callable delegator'
    ) {
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

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage($contains);
        $serviceManager->get(stdClass::class);
    }

    /**
     * @group mutation
     * @covers \Zend\ServiceManager\ServiceManager::setAlias
     */
    public function testCanInjectAliases()
    {
        $container = $this->createContainer([
            'factories' => [
                'foo' => function () {
                    return new stdClass;
                }
            ],
        ]);

        $container->setAlias('bar', 'foo');

        $foo = $container->get('foo');
        $bar = $container->get('bar');
        $this->assertInstanceOf(stdClass::class, $foo);
        $this->assertInstanceOf(stdClass::class, $bar);
        $this->assertSame($foo, $bar);
    }

    /**
     * @group mutation
     * @covers \Zend\ServiceManager\ServiceManager::setInvokableClass
     */
    public function testCanInjectInvokables()
    {
        $container = $this->createContainer();
        $container->setInvokableClass('foo', stdClass::class);
        $this->assertTrue($container->has('foo'));
        $this->assertTrue($container->has(stdClass::class));
        $foo = $container->get('foo');
        $this->assertInstanceOf(stdClass::class, $foo);
    }

    /**
     * @group mutation
     * @covers \Zend\ServiceManager\ServiceManager::setFactory
     */
    public function testCanInjectFactories()
    {
        $instance  = new stdClass;
        $container = $this->createContainer();

        $container->setFactory('foo', function () use ($instance) {
            return $instance;
        });
        $this->assertTrue($container->has('foo'));
        $foo = $container->get('foo');
        $this->assertSame($instance, $foo);
    }

    /**
     * @group mutation
     * @covers \Zend\ServiceManager\ServiceManager::mapLazyService
     */
    public function testCanMapLazyServices()
    {
        $container = $this->createContainer();
        $container->mapLazyService('foo', __CLASS__);
        $r = new ReflectionProperty($container, 'lazyServices');
        $r->setAccessible(true);
        $lazyServices = $r->getValue($container);
        $this->assertArrayHasKey('class_map', $lazyServices);
        $this->assertArrayHasKey('foo', $lazyServices['class_map']);
        $this->assertEquals(__CLASS__, $lazyServices['class_map']['foo']);
    }

    /**
     * @group mutation
     * @covers \Zend\ServiceManager\ServiceManager::addAbstractFactory
     */
    public function testCanInjectAbstractFactories()
    {
        $container = $this->createContainer();
        $container->addAbstractFactory(TestAsset\SimpleAbstractFactory::class);
        // @todo Remove "true" flag once #49 is merged
        $this->assertTrue($container->has(stdClass::class, true));
        $instance = $container->get(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $instance);
    }

    /**
     * @group mutation
     * @covers \Zend\ServiceManager\ServiceManager::addDelegator
     */
    public function testCanInjectDelegators()
    {
        $container = $this->createContainer([
            'factories' => [
                'foo' => function () {
                    return new stdClass;
                }
            ],
        ]);
        $container->addDelegator('foo', function ($container, $name, $callback) {
            $instance = $callback();
            $instance->name = $name;
            return $instance;
        });

        $foo = $container->get('foo');
        $this->assertInstanceOf(stdClass::class, $foo);
        $this->assertAttributeEquals('foo', 'name', $foo);
    }

    /**
     * @group mutation
     * @covers \Zend\ServiceManager\ServiceManager::addInitializer
     */
    public function testCanInjectInitializers()
    {
        $container = $this->createContainer([
            'factories' => [
                'foo' => function () {
                    return new stdClass;
                }
            ],
        ]);
        $container->addInitializer(function ($container, $instance) {
            if (! $instance instanceof stdClass) {
                return;
            }
            $instance->name = stdClass::class;
            return $instance;
        });

        $foo = $container->get('foo');
        $this->assertInstanceOf(stdClass::class, $foo);
        $this->assertAttributeEquals(stdClass::class, 'name', $foo);
    }

    /**
     * @group mutation
     * @covers \Zend\ServiceManager\ServiceManager::setService
     */
    public function testCanInjectServices()
    {
        $container = $this->createContainer();
        $container->setService('foo', $this);
        $this->assertSame($this, $container->get('foo'));
    }

    /**
     * @group mutation
     * @covers \Zend\ServiceManager\ServiceManager::setShared
     */
    public function testCanInjectSharingRules()
    {
        $container = $this->createContainer([
            'factories' => [
                'foo' => function () {
                    return new stdClass;
                }
            ],
        ]);
        $container->setShared('foo', false);
        $first  = $container->get('foo');
        $second = $container->get('foo');
        $this->assertNotSame($first, $second);
    }

    public function methodsAffectedByOverrideSettings()
    {
        // @codingStandardsIgnoreStart
        //  name                        => [ 'method to invoke',  [arguments for invocation]]
        return [
            'setAlias'                  => ['setAlias',           ['foo', 'bar']],
            'setInvokableClass'         => ['setInvokableClass',  ['foo', __CLASS__]],
            'setFactory'                => ['setFactory',         ['foo', function () {}]],
            'setService'                => ['setService',         ['foo', $this]],
            'setShared'                 => ['setShared',          ['foo', false]],
            'mapLazyService'            => ['mapLazyService',     ['foo', __CLASS__]],
            'addDelegator'              => ['addDelegator',       ['foo', function () {}]],
            'configure-alias'           => ['configure',          [['aliases'       => ['foo' => 'bar']]]],
            'configure-invokable'       => ['configure',          [['invokables'    => ['foo' => 'foo']]]],
            'configure-invokable-alias' => ['configure',          [['invokables'    => ['foo' => 'bar']]]],
            'configure-factory'         => ['configure',          [['factories'     => ['foo' => function () {}]]]],
            'configure-service'         => ['configure',          [['services'      => ['foo' => $this]]]],
            'configure-shared'          => ['configure',          [['shared'        => ['foo' => false]]]],
            'configure-lazy-service'    => ['configure',          [['lazy_services' => ['class_map' => ['foo' => __CLASS__]]]]],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider methodsAffectedByOverrideSettings
     * @group mutation
     */
    public function testConfiguringInstanceRaisesExceptionIfAllowOverrideIsFalse($method, $args)
    {
        $container = $this->createContainer(['services' => ['foo' => $this]]);
        $container->setAllowOverride(false);
        $this->expectException(ContainerModificationsNotAllowedException::class);
        call_user_func_array([$container, $method], $args);
    }

    /**
     * @group mutation
     */
    public function testAllowOverrideFlagIsFalseByDefault()
    {
        $container = $this->createContainer();
        $this->assertFalse($container->getAllowOverride());
        return $container;
    }

    /**
     * @group mutation
     * @depends testAllowOverrideFlagIsFalseByDefault
     */
    public function testAllowOverrideFlagIsMutable($container)
    {
        $container->setAllowOverride(true);
        $this->assertTrue($container->getAllowOverride());
    }

    /**
     * @group migration
     */
    public function testCanRetrieveParentContainerViaGetServiceLocatorWithDeprecationNotice()
    {
        $container = $this->createContainer();
        set_error_handler(function ($errno, $errstr) {
            $this->assertEquals(E_USER_DEPRECATED, $errno);
        }, E_USER_DEPRECATED);
        $this->assertSame($this->creationContext, $container->getServiceLocator());
        restore_error_handler();
    }

    /**
     * @group zendframework/zend-servicemanager#83
     */
    public function testCrashesOnCyclicAliases()
    {
        $this->expectException(CyclicAliasException::class);

        $this->createContainer([
            'aliases' => [
                'a' => 'b',
                'b' => 'a',
            ],
        ]);
    }
}
