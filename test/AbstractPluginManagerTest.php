<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\SimplePluginManager;

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
        $invokableFactory = $this->getMock(FactoryInterface::class);

        $config = [
            'factories' => [
                InvokableObject::class => $invokableFactory,
            ],
        ];

        $container     = $this->getMock(ContainerInterface::class);
        $pluginManager = new SimplePluginManager($container, $config);

        $invokableFactory->expects($this->once())
                         ->method('__invoke')
                         ->with($container, InvokableObject::class)
                         ->will($this->returnValue(new InvokableObject()));

        $object = $pluginManager->get(InvokableObject::class);

        $this->assertInstanceOf(InvokableObject::class, $object);
    }

    public function testValidateInstance()
    {
        $config = [
            'factories' => [
                InvokableObject::class => new InvokableFactory(),
                stdClass::class        => new InvokableFactory(),
            ],
        ];

        $container     = $this->getMock(ContainerInterface::class);
        $pluginManager = new SimplePluginManager($container, $config);

        // Assert no exception is triggered because the plugin manager validate ObjectWithOptions
        $pluginManager->get(InvokableObject::class);

        // Assert it throws an exception for anything else
        $this->setExpectedException(InvalidServiceException::class);
        $pluginManager->get(stdClass::class);
    }

    public function testCachesInstanceByDefaultIfNoOptionsArePassed()
    {
        $config = [
            'factories' => [
                InvokableObject::class => new InvokableFactory(),
            ],
        ];

        $container     = $this->getMock(ContainerInterface::class);
        $pluginManager = new SimplePluginManager($container, $config);

        $first  = $pluginManager->get(InvokableObject::class);
        $second = $pluginManager->get(InvokableObject::class);
        $this->assertInstanceOf(InvokableObject::class, $first);
        $this->assertInstanceOf(InvokableObject::class, $second);
        $this->assertSame($first, $second);
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

        $container     = $this->getMock(ContainerInterface::class);
        $pluginManager = new SimplePluginManager($container, $config);

        $first  = $pluginManager->get(InvokableObject::class, $options);
        $second = $pluginManager->get(InvokableObject::class, $options);
        $this->assertInstanceOf(InvokableObject::class, $first);
        $this->assertInstanceOf(InvokableObject::class, $second);
        $this->assertNotSame($first, $second);
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
        $this->assertTrue(isset($instance->option), 'Delegator-injected option was not found');
        $this->assertEquals(
            $config['option'],
            $instance->option,
            'Delegator-injected option does not match configuration'
        );
        $this->assertEquals('bar', $instance->foo);
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
     * @group migration
     */
    public function testCallingSetServiceLocatorSetsCreationContextWithDeprecationNotice()
    {
        set_error_handler(function ($errno, $errstr) {
            $this->assertEquals(E_USER_DEPRECATED, $errno);
        }, E_USER_DEPRECATED);
        $pluginManager = new TestAsset\LenientPluginManager();
        restore_error_handler();

        $this->assertAttributeSame($pluginManager, 'creationContext', $pluginManager);
        $serviceManager = new ServiceManager();

        set_error_handler(function ($errno, $errstr) {
            $this->assertEquals(E_USER_DEPRECATED, $errno);
        }, E_USER_DEPRECATED);
        $pluginManager->setServiceLocator($serviceManager);
        restore_error_handler();

        $this->assertAttributeSame($serviceManager, 'creationContext', $pluginManager);
    }

    /**
     * @group migration
     */
    public function testPassingNoInitialConstructorArgumentSetsPluginManagerAsCreationContextWithDeprecationNotice()
    {
        set_error_handler(function ($errno, $errstr) {
            $this->assertEquals(E_USER_DEPRECATED, $errno);
        }, E_USER_DEPRECATED);
        $pluginManager = new TestAsset\LenientPluginManager();
        restore_error_handler();
        $this->assertAttributeSame($pluginManager, 'creationContext', $pluginManager);
    }

    /**
     * @group migration
     */
    public function testCanPassConfigInterfaceAsFirstConstructorArgumentWithDeprecationNotice()
    {
        $config = $this->prophesize(ConfigInterface::class);
        $config->toArray()->willReturn([]);

        set_error_handler(function ($errno, $errstr) {
            $this->assertEquals(E_USER_DEPRECATED, $errno);
        }, E_USER_DEPRECATED);
        $pluginManager = new TestAsset\LenientPluginManager($config->reveal());
        restore_error_handler();

        $this->assertAttributeSame($pluginManager, 'creationContext', $pluginManager);
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
        $this->setExpectedException(InvalidArgumentException::class);
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
            $this->assertEquals(E_USER_DEPRECATED, $errno);
        }, E_USER_DEPRECATED);
        $pluginManager = new TestAsset\LenientPluginManager($config->reveal(), ['services' => [__CLASS__ => []]]);
        restore_error_handler();

        $this->assertSame($this, $pluginManager->get(__CLASS__));
    }
}
