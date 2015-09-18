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
use Interop\Container\Exception\ContainerException;
use PHPUnit_Framework_Assert as Assert;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Initializer\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
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

    /**
     * @param array $config
     *
     * @return ServiceManager
     */
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

        Assert::assertSame($object1, $object2);
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

        Assert::assertNotSame($object1, $object2);
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

        Assert::assertNotSame($object1, $object2);
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

        Assert::assertSame($object1, $object2);
    }

    public function testCanBuildObjectWithInvokableFactory()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                InvokableObject::class => InvokableFactory::class
            ]
        ]);

        $object = $serviceManager->build(InvokableObject::class, ['foo' => 'bar']);

        Assert::assertInstanceOf(InvokableObject::class, $object);
        Assert::assertEquals(['foo' => 'bar'], $object->options);
    }

    public function testCanCreateObjectWithClosureFactory()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => function (ServiceLocatorInterface $serviceLocator, $className) {
                    Assert::assertEquals(stdClass::class, $className);
                    return new stdClass();
                }
            ]
        ]);

        $object = $serviceManager->get(stdClass::class);
        Assert::assertInstanceOf(stdClass::class, $object);
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

        Assert::assertInstanceOf(InvokableObject::class, $object);
        Assert::assertTrue($serviceManager->has('bar'));
        Assert::assertFalse($serviceManager->has('baz'));
    }

    public function testCanCheckServiceExistenceWithoutCheckingAbstractFactories()
    {
        $serviceManager = $this->createContainer([
            'factories' => [
                stdClass::class => InvokableFactory::class
            ]
        ]);

        Assert::assertTrue($serviceManager->has(stdClass::class));
        Assert::assertFalse($serviceManager->has(DateTime::class));
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

        Assert::assertTrue($serviceManager->has(stdClass::class, true));
        Assert::assertTrue($serviceManager->has(DateTime::class, true));
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

        Assert::assertNotSame($object1, $object2);
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

        $initializer->expects(TestCase::once())
                    ->method('__invoke')
                    ->with($this->creationContext, TestCase::isInstanceOf(stdClass::class));

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

        Assert::assertTrue($serviceManager->has(DateTime::class));
        Assert::assertFalse($serviceManager->has(stdClass::class));

        Assert::assertTrue($newServiceManager->has(DateTime::class));
        Assert::assertTrue($newServiceManager->has(stdClass::class));

        // Make sure the context has been updated for the new container
        Assert::assertAttributeSame($newServiceManager, 'creationContext', $newServiceManager);
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

        $firstFactory->expects(TestCase::never())->method('__invoke');
        $secondFactory->expects(TestCase::once())->method('__invoke');

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
        Assert::assertFalse($serviceManager->has('Some\Made\Up\Entry'));
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
        Assert::assertTrue($serviceManager->has(stdClass::class));
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
        Assert::assertTrue($serviceManager->has(stdClass::class));
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

        Assert::assertFalse($serviceManager->has(DateTime::class));
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
    public function testHasCanCheckAbstractFactoriesWhenRequested($abstractFactory, $expected)
    {
        $serviceManager = $this->createContainer([
            'abstract_factories' => [
                $abstractFactory,
            ],
        ]);

        Assert::assertSame($expected, $serviceManager->has(DateTime::class, true));
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
        Assert::assertInstanceOf(DateTime::class, $dateTime, 'DateTime service did not resolve as expected');
        $notShared = $serviceManager->get(DateTime::class);
        Assert::assertInstanceOf(DateTime::class, $notShared, 'DateTime service did not re-resolve as expected');
        Assert::assertNotSame(
            $dateTime,
            $notShared,
            'Expected unshared instances for DateTime service but received shared instances'
        );

        $config = $serviceManager->get('config');
        Assert::assertInternalType('array', $config, 'Config service did not resolve as expected');
        Assert::assertSame(
            $config,
            $serviceManager->get('config'),
            'Config service resolved as unshared instead of shared'
        );

        $stdClass = $serviceManager->get(stdClass::class);
        Assert::assertInstanceOf(stdClass::class, $stdClass, 'stdClass service did not resolve as expected');
        Assert::assertSame(
            $stdClass,
            $serviceManager->get(stdClass::class),
            'stdClass service should be shared, but resolved as unshared'
        );
        Assert::assertTrue(
            isset($stdClass->foo),
            'Expected delegator to inject "foo" property in stdClass service, but it was not'
        );
        Assert::assertEquals('bar', $stdClass->foo, 'stdClass "foo" property was not injected correctly');
        Assert::assertTrue(
            isset($stdClass->bar),
            'Expected initializer to inject "bar" property in stdClass service, but it was not'
        );
        Assert::assertEquals('baz', $stdClass->bar, 'stdClass "bar" property was not injected correctly');
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
        Assert::assertInstanceOf(DateTime::class, $dateTime);
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
        $this->setExpectedException(InvalidArgumentException::class, $contains);
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
        Assert::assertInstanceOf(stdClass::class, $instance);
        Assert::assertTrue(isset($instance->foo), '"foo" property was not injected by initializer');
        Assert::assertEquals('bar', $instance->foo, '"foo" property was not properly injected');
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
        $this->setExpectedException(InvalidArgumentException::class, $contains);
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
        $this->setExpectedException(ContainerException::class, 'Unable to resolve');
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

        $this->setExpectedException(ServiceNotCreatedException::class, $contains);
        $serviceManager->get(stdClass::class);
    }
}
