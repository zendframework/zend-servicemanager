<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use stdClass;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\SimplePluginManager;
use ZendTest\ServiceManager\TestAsset\V2v3PluginManager;

/**
 * @covers \Zend\ServiceManager\AbstractPluginManager
 */
class AbstractPluginManagerTest extends TestCase
{
    use CommonServiceLocatorBehaviorsTrait;

    public function createContainer(array $config = [])
    {
        $this->creationContext = new ServiceManager();
        return new TestAsset\LenientPluginManager($this->creationContext, $config);
    }

    public function testInjectCreationContextInFactories()
    {
        $invokableFactory = $this->getMockBuilder(FactoryInterface::class)
            ->getMock();

        $config = [
            'factories' => [
                InvokableObject::class => $invokableFactory,
            ],
        ];

        $container     = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();
        $pluginManager = new SimplePluginManager($container, $config);

        $invokableFactory->expects($this->once())
                         ->method('__invoke')
                         ->with($container, InvokableObject::class)
                         ->will($this->returnValue(new InvokableObject()));

        $object = $pluginManager->get(InvokableObject::class);

        self::assertInstanceOf(InvokableObject::class, $object);
    }

    public function testValidateInstance()
    {
        $config = [
            'factories' => [
                InvokableObject::class => new InvokableFactory(),
                stdClass::class        => new InvokableFactory(),
            ],
        ];

        $container     = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();
        $pluginManager = new SimplePluginManager($container, $config);

        // Assert no exception is triggered because the plugin manager validate ObjectWithOptions
        $pluginManager->get(InvokableObject::class);

        // Assert it throws an exception for anything else
        $this->expectException(InvalidServiceException::class);
        $pluginManager->get(stdClass::class);
    }

    public function testCachesInstanceByDefaultIfNoOptionsArePassed()
    {
        $config = [
            'factories' => [
                InvokableObject::class => new InvokableFactory(),
            ],
        ];

        $container     = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();
        $pluginManager = new SimplePluginManager($container, $config);

        $first  = $pluginManager->get(InvokableObject::class);
        $second = $pluginManager->get(InvokableObject::class);
        self::assertInstanceOf(InvokableObject::class, $first);
        self::assertInstanceOf(InvokableObject::class, $second);
        self::assertSame($first, $second);
    }

    public function shareByDefaultSettings()
    {
        return [
            'true'  => [true],
            'false' => [false],
        ];
    }

    /**
     * @dataProvider shareByDefaultSettings
     */
    public function testReturnsDiscreteInstancesIfOptionsAreProvidedRegardlessOfShareByDefaultSetting($shareByDefault)
    {
        $config = [
            'factories' => [
                InvokableObject::class => new InvokableFactory(),
            ],
            'share_by_default' => $shareByDefault,
        ];
        $options = ['foo' => 'bar'];

        $container     = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();
        $pluginManager = new SimplePluginManager($container, $config);

        $first  = $pluginManager->get(InvokableObject::class, $options);
        $second = $pluginManager->get(InvokableObject::class, $options);
        self::assertInstanceOf(InvokableObject::class, $first);
        self::assertInstanceOf(InvokableObject::class, $second);
        self::assertNotSame($first, $second);
    }

    /**
     * Separate test from ServiceManager, as all factories go through the
     * creation context; we need to configure the parent container, as
     * the delegator factory will be receiving that.
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
        ]);
        $pluginManager = new TestAsset\LenientPluginManager($serviceManager, [
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

        $instance = $pluginManager->get(stdClass::class);
        self::assertTrue(isset($instance->option), 'Delegator-injected option was not found');
        self::assertEquals(
            $config['option'],
            $instance->option,
            'Delegator-injected option does not match configuration'
        );
        self::assertEquals('bar', $instance->foo);
    }

    /**
     * Overrides the method in the CommonServiceLocatorBehaviorsTrait, due to behavior differences.
     *
     * @covers \Zend\ServiceManager\AbstractPluginManager::get
     */
    public function testGetRaisesExceptionWhenNoFactoryIsResolved()
    {
        $pluginManager = $this->createContainer();
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(get_class($pluginManager));
        $pluginManager->get('Some\Unknown\Service');
    }

    /**
     * @group migration
     */
    public function testCallingSetServiceLocatorSetsCreationContextWithDeprecationNotice()
    {
        set_error_handler(function ($errno, $errstr) {
            self::assertEquals(E_USER_DEPRECATED, $errno);
        }, E_USER_DEPRECATED);
        $pluginManager = new TestAsset\LenientPluginManager();
        restore_error_handler();

        self::assertAttributeSame($pluginManager, 'creationContext', $pluginManager);
        $serviceManager = new ServiceManager();

        set_error_handler(function ($errno, $errstr) {
            self::assertEquals(E_USER_DEPRECATED, $errno);
        }, E_USER_DEPRECATED);
        $pluginManager->setServiceLocator($serviceManager);
        restore_error_handler();

        self::assertAttributeSame($serviceManager, 'creationContext', $pluginManager);
    }

    /**
     * @group migration
     */
    public function testPassingNoInitialConstructorArgumentSetsPluginManagerAsCreationContextWithDeprecationNotice()
    {
        set_error_handler(function ($errno, $errstr) {
            self::assertEquals(E_USER_DEPRECATED, $errno);
        }, E_USER_DEPRECATED);
        $pluginManager = new TestAsset\LenientPluginManager();
        restore_error_handler();
        self::assertAttributeSame($pluginManager, 'creationContext', $pluginManager);
    }

    /**
     * @group migration
     */
    public function testCanPassConfigInterfaceAsFirstConstructorArgumentWithDeprecationNotice()
    {
        $config = $this->prophesize(ConfigInterface::class);
        $config->toArray()->willReturn([]);

        set_error_handler(function ($errno, $errstr) {
            self::assertEquals(E_USER_DEPRECATED, $errno);
        }, E_USER_DEPRECATED);
        $pluginManager = new TestAsset\LenientPluginManager($config->reveal());
        restore_error_handler();

        self::assertAttributeSame($pluginManager, 'creationContext', $pluginManager);
    }

    public function invalidConstructorArguments()
    {
        return [
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'string'     => ['invalid'],
            'array'      => [['invokables' => []]],
            'object'     => [(object) ['invokables' => []]],
        ];
    }

    /**
     * @group migration
     * @dataProvider invalidConstructorArguments
     */
    public function testPassingNonContainerNonConfigNonNullFirstConstructorArgumentRaisesException($arg)
    {
        $this->expectException(InvalidArgumentException::class);
        new TestAsset\LenientPluginManager($arg);
    }

    /**
     * @group migration
     */
    public function testPassingConfigInstanceAsFirstConstructorArgumentSkipsSecondArgumentWithDeprecationNotice()
    {
        $config = $this->prophesize(ConfigInterface::class);
        $config->toArray()->willReturn(['services' => [__CLASS__ => $this]]);

        set_error_handler(function ($errno, $errstr) {
            self::assertEquals(E_USER_DEPRECATED, $errno);
        }, E_USER_DEPRECATED);
        $pluginManager = new TestAsset\LenientPluginManager($config->reveal(), ['services' => [__CLASS__ => []]]);
        restore_error_handler();

        self::assertSame($this, $pluginManager->get(__CLASS__));
    }

    /**
     * @group migration
     * @group autoinvokable
     */
    public function testAutoInvokableServicesAreNotKnownBeforeRetrieval()
    {
        $pluginManager = new TestAsset\SimplePluginManager(new ServiceManager());
        self::assertFalse($pluginManager->has(TestAsset\InvokableObject::class));
    }

    /**
     * @group migration
     * @group autoinvokable
     */
    public function testSupportsRetrievingAutoInvokableServicesByDefault()
    {
        $pluginManager = new TestAsset\SimplePluginManager(new ServiceManager());
        $invokable = $pluginManager->get(TestAsset\InvokableObject::class);
        self::assertInstanceOf(TestAsset\InvokableObject::class, $invokable);
    }

    /**
     * @group migration
     * @group autoinvokable
     */
    public function testPluginManagersMayOptOutOfSupportingAutoInvokableServices()
    {
        $pluginManager = new TestAsset\NonAutoInvokablePluginManager(new ServiceManager());
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(TestAsset\NonAutoInvokablePluginManager::class);
        $pluginManager->get(TestAsset\InvokableObject::class);
    }

    /**
     * @group migration
     */
    public function testValidateWillFallBackToValidatePluginWhenDefinedAndEmitDeprecationNotice()
    {
        $assertionCalled = false;
        $instance = (object) [];
        $assertion = function ($plugin) use ($instance, &$assertionCalled) {
            self::assertSame($instance, $plugin);
            $assertionCalled = true;
        };
        $pluginManager = new TestAsset\V2ValidationPluginManager(new ServiceManager());
        $pluginManager->assertion = $assertion;

        $errorHandlerCalled = false;
        set_error_handler(function ($errno, $errmsg) use (&$errorHandlerCalled) {
            self::assertEquals(E_USER_DEPRECATED, $errno);
            self::assertContains('3.0', $errmsg);
            $errorHandlerCalled = true;
        }, E_USER_DEPRECATED);
        $pluginManager->validate($instance);
        restore_error_handler();

        self::assertTrue($assertionCalled, 'Assertion was not called by validatePlugin!');
        self::assertTrue($errorHandlerCalled, 'Error handler was not triggered by validatePlugin!');
    }

    public function testSetServiceShouldRaiseExceptionForInvalidPlugin()
    {
        $pluginManager = new TestAsset\SimplePluginManager(new ServiceManager());
        $this->expectException(InvalidServiceException::class);
        $pluginManager->setService(stdClass::class, new stdClass());
    }

    public function testPassingServiceInstanceViaConfigureShouldRaiseExceptionForInvalidPlugin()
    {
        $pluginManager = new TestAsset\SimplePluginManager(new ServiceManager());
        $this->expectException(InvalidServiceException::class);
        $pluginManager->configure(['services' => [
            stdClass::class => new stdClass(),
        ]]);
    }

    /**
     * @group 79
     * @group 78
     */
    public function testAbstractFactoryGetsCreationContext()
    {
        $serviceManager = new ServiceManager();
        $pluginManager = new TestAsset\SimplePluginManager($serviceManager);
        $abstractFactory = $this->prophesize(AbstractFactoryInterface::class);
        $abstractFactory->canCreate($serviceManager, 'foo')
            ->willReturn(true);
        $abstractFactory->__invoke($serviceManager, 'foo', null)
            ->willReturn(new InvokableObject());
        $pluginManager->addAbstractFactory($abstractFactory->reveal());
        self::assertInstanceOf(InvokableObject::class, $pluginManager->get('foo'));
    }

    public function testAliasPropertyResolves()
    {
        $pluginManager = new V2v3PluginManager(new ServiceManager());
        self::assertInstanceOf(InvokableObject::class, $pluginManager->get('foo'));
    }
}
