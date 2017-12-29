<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ServiceManager\TestAsset\Car;
use ZendTest\ServiceManager\TestAsset\CarFactory;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\SimpleServiceManager;
use ZendTest\ServiceManager\TestAsset\OffRoaderFactory;
use ZendTest\ServiceManager\TestAsset\OffRoader;

/**
 * @covers \Zend\ServiceManager\ServiceManager
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
        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testConfigurationCanBeMerged()
    {
        $serviceManager = new SimpleServiceManager([
            'factories' => [
                DateTime::class => InvokableFactory::class
            ]
        ]);

        $this->assertTrue($serviceManager->has(DateTime::class));
        // stdClass service is inlined in SimpleServiceManager
        $this->assertTrue($serviceManager->has(stdClass::class));
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
     * @covers \Zend\ServiceManager\ServiceManager::doCreate
     * @covers \Zend\ServiceManager\ServiceManager::createDelegatorFromName
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
        $this->assertTrue(isset($instance->option), 'Delegator-injected option was not found');
        $this->assertEquals(
            $config['option'],
            $instance->option,
            'Delegator-injected option does not match configuration'
        );
        $this->assertEquals('bar', $instance->foo);
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

        $this->assertEquals($shouldBeSameInstance, $a === $b);
    }

    public function testMapsOneToOneInvokablesAsInvokableFactoriesInternally()
    {
        $config = [
            'invokables' => [
                InvokableObject::class => InvokableObject::class,
            ],
        ];

        $serviceManager = new ServiceManager($config);
        $this->assertAttributeSame([
            InvokableObject::class => InvokableFactory::class,
        ], 'factories', $serviceManager, 'Invokable object factory not found');
    }

    public function testMapsNonSymmetricInvokablesAsAliasPlusInvokableFactory()
    {
        $config = [
            'invokables' => [
                'Invokable' => InvokableObject::class,
            ],
        ];

        $serviceManager = new ServiceManager($config);
        $this->assertAttributeSame([
            'Invokable' => InvokableObject::class,
        ], 'aliases', $serviceManager, 'Alias not found for non-symmetric invokable');
        $this->assertAttributeSame([
            InvokableObject::class => InvokableFactory::class,
        ], 'factories', $serviceManager, 'Factory not found for non-symmetric invokable target');
    }

    /**
     * @depends testMapsNonSymmetricInvokablesAsAliasPlusInvokableFactory
     */
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

        $this->assertNotSame($instance1, $instance2);
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

        $this->assertNotSame($instance1, $instance2);
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

        $this->assertSame($service, $alias);
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

        $this->assertSame($service, $alias);
        $this->assertSame($service, $headAlias);
    }

    public static function sampleFactory()
    {
        return new stdClass();
    }

    public function testFactoryMayBeStaticMethodDescribedByCallableString()
    {
        $config = [
            'factories' => [
                stdClass::class => 'ZendTest\ServiceManager\ServiceManagerTest::sampleFactory',
            ]
        ];
        $serviceManager = new SimpleServiceManager($config);
        $this->assertEquals(stdClass::class, get_class($serviceManager->get(stdClass::class)));
    }

    public function testCreateAliasedServices()
    {
        $sm = new ServiceManager(
            [
                'services' =>
                [
                    Car::class => new Car(),
                    Car::class . 'noFactory' => new Car()
                ],
                'factories' =>
                [
                    Car::class => CarFactory::class,
                    OffRoader::class => OffRoaderFactory::class,
                ],
                'aliases' =>
                [
                     // not shared (see 'shared' below)
                    'alias1' => Car::class . 'noFactory',
                    // shared by default (see 'shared' below)
                    'alias2' => Car::class . 'noFactory',
                    // not shared (see 'shared' below)
                    'alias3' => Car::class,
                    // shared (see 'shared' below)
                    'alias4' => OffRoader::class,
                ],
                'shared' =>
                [
                    Car::class  => true,
                    'alias1'    => false,
                    'alias2'    => true,
                    'alias3'    => false,
                    'alias4'    => true,
                ],
            ]
        );
        // non-alias requests (retrieved directly from services member array)
        $service1 = $sm->get(Car::class);
        $service2 = $sm->get(Car::class);

        // service1 and service2 both should not claim to be a clone
        self::assertSame($service1->classifier, 'I am not a clone, honestly.');
        self::assertSame($service2->classifier, 'I am not a clone, honestly.');
        // service1 and service2 should reference the same object
        self::assertSame($service1, $service2);
        // both should be an immediate Car instance
        self::assertSame(get_class($service1), Car::class);
        self::assertSame(get_class($service2), Car::class);


        // non-shared alias requests (should be created via clone)
        $alias11 = $sm->get('alias1');
        $alias12 = $sm->get('alias1');
        // non-shared alias services should both be Cars
        self::assertSame(get_class($alias11), Car::class);
        self::assertSame(get_class($alias12), Car::class);
        // non-shared alias services should be tagged as clones
        self::assertSame($alias11->classifier, 'I am a cloned car, believe me.');
        self::assertSame($alias12->classifier, 'I am a cloned car, believe me.');
        // non-shared alias services should be different objects
        self::assertNotSame($alias11, $alias12);
        // both non-shared aliases should be different from the shared service
        self::assertNotSame($alias11, $sm->get(Car::class . 'noFactory'));
        self::assertNotSame($alias12, $sm->get(Car::class . 'noFactory'));

        // shared alias requests (should be retrieved via services member array)
        $alias21 = $sm->get('alias2');
        $alias22 = $sm->get('alias2');
        // shared alias services should both be Cars
        self::assertSame(get_class($alias21), Car::class);
        self::assertSame(get_class($alias22), Car::class);
        // shared alias services should not claim to be clones
        self::assertSame($alias21->classifier, 'I am not a clone, honestly.');
        self::assertSame($alias22->classifier, 'I am not a clone, honestly.');
        // shared alias services should be the same object
        self::assertSame($alias21, $alias22);
        // shared alias services should be idsentical to the shared service
        self::assertSame($alias21, $sm->get(Car::class . 'noFactory'));

        // mixed alias requests
        $alias1 = $sm->get('alias1');
        $alias2 = $sm->get('alias2');
        // alias services should make different claims
        // (both of the next two asserts are redundant, I know)
        self::assertSame($alias1->classifier, 'I am a cloned car, believe me.');
        self::assertSame($alias2->classifier, 'I am not a clone, honestly.');
        // shared alias services should be the same object
        self::assertNotSame($alias1, $alias2);

        // non-shared alias request with factory
        $alias31 = $sm->get('alias3');
        $alias32 = $sm->get('alias3');
        // both should be ClonedCars
        self::assertSame(get_class($alias31), Car::class);
        self::assertSame(get_class($alias32), Car::class);
        // both should be produced by the CarFactory
        self::assertSame($alias31->classifier, 'I was created by a car factory, no diesel issues, I promise');
        self::assertSame($alias32->classifier, 'I was created by a car factory, no diesel issues, I promise');
        // both should be different objects
        self::assertNotSame($alias31, $alias32);

        // shared alias request with factory (here is quirks somewhere)
        $alias41 = $sm->get('alias4');
        $alias42 = $sm->get('alias4');
        // both should be ClonedCars
        self::assertSame(get_class($alias41), Car::class);
        self::assertSame(get_class($alias42), Car::class);
        // both should be produced by the CarFactory
        self::assertSame($alias41->classifier, 'I am a factory produced offroader.');
        // Retrieve the shared service directly and modify the classifier
        // to proof that the second call does not invoke the OffRoaderFactory.
        $sm->get(OffRoader::class)->classifier = 'I am a shared offroader.';
        self::assertSame($alias42->classifier, 'I am a shared offroader.');
        // both should be different objects
        self::assertSame($alias41, $alias42);
        self::assertSame($alias41, $sm->get(OffRoader::class));
    }
}
