<?php
/**
 * @link      https://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager;

use DateTime;
use PHPUnit\Framework\MockObject\Invokable;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Mxc\ServiceManager\Factory\AbstractFactoryInterface;
use Mxc\ServiceManager\Factory\FactoryInterface;
use Mxc\ServiceManager\Factory\InvokableFactory;
use Mxc\ServiceManager\ServiceManager;
use MxcTest\ServiceManager\TestAsset\Foo;
use MxcTest\ServiceManager\TestAsset\InvokableObject;
use MxcTest\ServiceManager\TestAsset\PreconfiguredServiceManager;
use MxcTest\ServiceManager\TestAsset\SampleFactory;
use MxcTest\ServiceManager\TestAsset\SimpleServiceManager;
use MxcTest\ServiceManager\TestAsset\TaggingDelegatorFactory;

/**
 * @covers \Mxc\ServiceManager\ServiceManager
 */
class ServiceManagerTest extends TestCase
{
    use CommonServiceLocatorBehaviorsTrait;

    public function createContainer(array $config = [])
    {
        $this->creationContext = new ServiceManager($config);
        return $this->creationContext;
    }

    public function testServiceManagerIsAPsr11Container()
    {
        $container = $this->createContainer();
        self::assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testConfigurationCanBeMerged()
    {
        $serviceManager = new SimpleServiceManager([
            'factories' => [
                DateTime::class => InvokableFactory::class
            ]
        ]);

        self::assertTrue($serviceManager->has(DateTime::class));
        // stdClass service is inlined in SimpleServiceManager
        self::assertTrue($serviceManager->has(stdClass::class));
    }

    public function testConfigurationTakesPrecedenceWhenMerged()
    {
        $factory = $this->getMockBuilder(FactoryInterface::class)
            ->getMock();

        $factory->expects($this->once())->method('__invoke');

        $serviceManager = new SimpleServiceManager([
            'factories' => [
                stdClass::class => $factory
            ]
        ]);

        $serviceManager->get(stdClass::class);
    }

    /**
     * @covers \Mxc\ServiceManager\ServiceManager::doCreate
     * @covers \Mxc\ServiceManager\ServiceManager::createDelegatorFromName
     */
    public function testCanWrapCreationInDelegators()
    {
        $config = [
            'option' => 'OPTIONED',
        ];
        $serviceManager = new ServiceManager([
            'services'  => [
                'config' => $config,
            ],
            'factories' => [
                stdClass::class => InvokableFactory::class,
            ],
            'delegators' => [
                stdClass::class => [
                    TestAsset\PreDelegator::class,
                    function ($container, $name, $callback) {
                        $instance = $callback();
                        $instance->foo = 'bar';
                        return $instance;
                    },
                ],
            ],
        ]);

        $instance = $serviceManager->get(stdClass::class);
        self::assertTrue(isset($instance->option), 'Delegator-injected option was not found');
        self::assertEquals(
            $config['option'],
            $instance->option,
            'Delegator-injected option does not match configuration'
        );
        self::assertEquals('bar', $instance->foo);
    }

    public function shareProvider()
    {
        $sharedByDefault          = true;
        $serviceShared            = true;
        $serviceDefined           = true;
        $shouldReturnSameInstance = true;

        // @codingStandardsIgnoreStart
        return [
            // Description => [$sharedByDefault, $serviceShared, $serviceDefined, $expectedInstance]
            'SharedByDefault: T, ServiceIsExplicitlyShared: T, ServiceIsDefined: T' => [ $sharedByDefault,  $serviceShared,  $serviceDefined,  $shouldReturnSameInstance],
            'SharedByDefault: T, ServiceIsExplicitlyShared: T, ServiceIsDefined: F' => [ $sharedByDefault,  $serviceShared, !$serviceDefined,  $shouldReturnSameInstance],
            'SharedByDefault: T, ServiceIsExplicitlyShared: F, ServiceIsDefined: T' => [ $sharedByDefault, !$serviceShared,  $serviceDefined, !$shouldReturnSameInstance],
            'SharedByDefault: T, ServiceIsExplicitlyShared: F, ServiceIsDefined: F' => [ $sharedByDefault, !$serviceShared, !$serviceDefined,  $shouldReturnSameInstance],
            'SharedByDefault: F, ServiceIsExplicitlyShared: T, ServiceIsDefined: T' => [!$sharedByDefault,  $serviceShared,  $serviceDefined,  $shouldReturnSameInstance],
            'SharedByDefault: F, ServiceIsExplicitlyShared: T, ServiceIsDefined: F' => [!$sharedByDefault,  $serviceShared, !$serviceDefined, !$shouldReturnSameInstance],
            'SharedByDefault: F, ServiceIsExplicitlyShared: F, ServiceIsDefined: T' => [!$sharedByDefault, !$serviceShared,  $serviceDefined, !$shouldReturnSameInstance],
            'SharedByDefault: F, ServiceIsExplicitlyShared: F, ServiceIsDefined: F' => [!$sharedByDefault, !$serviceShared, !$serviceDefined, !$shouldReturnSameInstance],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider shareProvider
     */
    public function testShareability($sharedByDefault, $serviceShared, $serviceDefined, $shouldBeSameInstance)
    {
        $config = [
            'shared_by_default' => $sharedByDefault,
            'factories'         => [
                stdClass::class => InvokableFactory::class,
            ]
        ];

        if ($serviceDefined) {
            $config['shared'] = [
                stdClass::class => $serviceShared
            ];
        }

        $serviceManager = new ServiceManager($config);

        $a = $serviceManager->get(stdClass::class);
        $b = $serviceManager->get(stdClass::class);

        self::assertEquals($shouldBeSameInstance, $a === $b);
    }

    public function testSharedServicesReferencingInvokableAliasShouldBeHonored()
    {
        $config = [
            'invokables' => [
                'Invokable' => InvokableObject::class,
            ],
            'shared' => [
                'Invokable' => false,
            ],
        ];

        $serviceManager = new ServiceManager($config);
        $instance1 = $serviceManager->get('Invokable');
        $instance2 = $serviceManager->get('Invokable');

        self::assertNotSame($instance1, $instance2);
    }

    public function testSharedServicesReferencingAliasShouldBeHonored()
    {
        $config = [
            'aliases' => [
                'Invokable' => InvokableObject::class,
            ],
            'factories' => [
                InvokableObject::class => InvokableFactory::class,
            ],
            'shared' => [
                'Invokable' => false,
            ],
        ];

        $serviceManager = new ServiceManager($config);
        $instance1 = $serviceManager->get('Invokable');
        $instance2 = $serviceManager->get('Invokable');

        self::assertNotSame($instance1, $instance2);
    }

    public function testAliasToAnExplicitServiceShouldWork()
    {
        $config = [
            'aliases' => [
                'Invokable' => InvokableObject::class,
            ],
            'services' => [
                InvokableObject::class => new InvokableObject(),
            ],
        ];

        $serviceManager = new ServiceManager($config);

        $service = $serviceManager->get(InvokableObject::class);
        $alias   = $serviceManager->get('Invokable');

        self::assertSame($service, $alias);
    }

    /**
     * @depends testAliasToAnExplicitServiceShouldWork
     */
    public function testSetAliasShouldWorkWithRecursiveAlias()
    {
        $config = [
            'aliases' => [
                'Alias' => 'TailInvokable',
            ],
            'services' => [
                InvokableObject::class => new InvokableObject(),
            ],
        ];
        $serviceManager = new ServiceManager($config);
        $serviceManager->setAlias('HeadAlias', 'Alias');
        $serviceManager->setAlias('TailInvokable', InvokableObject::class);

        $service   = $serviceManager->get(InvokableObject::class);
        $alias     = $serviceManager->get('Alias');
        $headAlias = $serviceManager->get('HeadAlias');

        self::assertSame($service, $alias);
        self::assertSame($service, $headAlias);
    }

    public function testAbstractFactoryShouldBeCheckedForResolvedAliasesInsteadOfAliasName()
    {
        $abstractFactory = $this->createMock(AbstractFactoryInterface::class);

        $serviceManager = new SimpleServiceManager([
            'aliases' => [
                'Alias' => 'ServiceName',
            ],
            'abstract_factories' => [
                $abstractFactory,
            ],
        ]);
        $abstractFactory
        ->expects($this->once())
        ->method('canCreate')
        ->with($this->anything(), $this->equalTo('ServiceName'))
        ->willReturn(true);
        $this->assertTrue($serviceManager->has('Alias'));
    }

    public static function sampleFactory()
    {
        return new stdClass();
    }

    public function testFactoryMayBeStaticMethodDescribedByCallableString()
    {
        $config = [
            'factories' => [
                stdClass::class => 'MxcTest\ServiceManager\ServiceManagerTest::sampleFactory',
            ]
        ];
        $serviceManager = new SimpleServiceManager($config);
        $this->assertEquals(stdClass::class, get_class($serviceManager->get(stdClass::class)));
    }

    public function testMemberBasedConfigurationGetsApplied()
    {
        $sm = new PreconfiguredServiceManager();

        // will be true if $aliases array is properly setup and
        // recursive alias resolution works
        $this->assertTrue($sm->has('alias1'));
        $this->assertInstanceOf(stdClass::class, $sm->get('alias1'));

        // will be true if $aliases array is properly setup and
        // simple alias resolution works
        $this->assertTrue($sm->has('alias2'));
        $this->assertInstanceOf(stdClass::class, $sm->get('alias2'));

        // will return true if $services array is properly setup
        $this->assertTrue($sm->has('service'));
        $this->assertInstanceOf(stdClass::class, $sm->get('service'));

        // will be true if factory array is properly setup
        $this->assertTrue($sm->has('delegator'));
        $this->assertInstanceOf(InvokableObject::class, $sm->get('delegator'));

        // will be true if initializer is present
        $this->assertTrue($sm->get('delegator')->initializerPresent);

        // will be true if factory array is properly setup
        $this->assertTrue($sm->has('factory'));
        $this->assertInstanceOf(InvokableObject::class, $sm->get('factory'));

        // will be true if initializer is present
        $this->assertTrue($sm->get('factory')->initializerPresent);

        // will succeed if invokable is properly set up
        $this->assertTrue($sm->has('invokable'));
        $this->assertInstanceOf(stdClass::class, $sm->get('invokable'));

        // will be true if initializer is present
        $this->assertTrue($sm->get('invokable')->initializerPresent);

        // will succeed if abstract factory is available
        $this->assertTrue($sm->has('foo'));
        $this->assertInstanceOf(Foo::class, $sm->get('foo'));

        // will be true if initializer is present
        $this->assertTrue($sm->get('foo')->initializerPresent);
    }

    public function testInvokablesShouldNotOverrideFactoriesAndDelegators()
    {
        $sm = new ServiceManager([
            'factories' => [
                // produce InvokableObject
                'factory1' => SampleFactory::class,
                'factory2' => SampleFactory::class,
            ],
            'delegators' => [
                'factory1' => [
                    // produce tagged invokable object
                    TaggingDelegatorFactory::class,
                ]
            ]
        ]);

        $object1 = $sm->build('factory1');
        // assert delegated object is produced by delegator factory
        $this->assertTrue(isset($object1->delegatorTag));
        $this->assertInstanceOf(InvokableObject::class, $object1);


        $object2 = $sm->build('factory2');
        // assert delegated object is produced by SampleFactory
        $this->assertFalse(isset($object2->delegatorTag));
        $this->assertInstanceOf(InvokableObject::class, $object2);

        $sm->setInvokableClass('factory1', stdClass::class);
        $sm->setInvokableClass('factory2', stdClass::class);

        $object1 = $sm->build('factory1');
        // assert delegated object is still produced by delegator factory
        $this->assertTrue(isset($object1->delegatorTag));
        $this->assertInstanceOf(InvokableObject::class, $object1);

        $object2 = $sm->build('factory2');
        // assert delegated object is still produced by SampleFactory
        $this->assertFalse(isset($object2->delegatorTag));
        $this->assertInstanceOf(InvokableObject::class, $object2);
    }
}
