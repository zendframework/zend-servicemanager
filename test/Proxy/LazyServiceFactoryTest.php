<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Proxy;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit\Framework\TestCase;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use ProxyManager\Proxy\VirtualProxyInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;
use Zend\ServiceManager\Proxy\LazyServiceFactory;

/**
 * @covers \Zend\ServiceManager\Proxy\LazyServiceFactory
 */
class LazyServiceFactoryTest extends TestCase
{
    /**
     * @var LazyServiceFactory
     */
    private $factory;

    /**
     * @var LazyLoadingValueHolderFactory|MockObject
     */
    private $proxyFactory;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->proxyFactory = $this->getMockBuilder(LazyLoadingValueHolderFactory::class)
            ->getMock();
        $servicesMap = [
            'fooService' => 'FooClass',
        ];

        $this->factory = new LazyServiceFactory($this->proxyFactory, $servicesMap);
    }

    public function testImplementsDelegatorFactoryInterface()
    {
        $this->assertInstanceOf(DelegatorFactoryInterface::class, $this->factory);
    }

    public function testThrowExceptionWhenServiceNotExists()
    {
        $callback = $this->getMockBuilder('stdClass')
            ->setMethods(['callback'])
            ->getMock();
        $callback->expects($this->never())
            ->method('callback');

        $container = $this->createContainerMock();

        $this->proxyFactory->expects($this->never())
            ->method('createProxy')
        ;
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('The requested service "not_exists" was not found in the provided services map');

        $this->factory->__invoke($container, 'not_exists', [$callback, 'callback']);
    }

    public function testCreates()
    {
        $callback = $this->getMockBuilder('stdClass')
            ->setMethods(['callback'])
            ->getMock();
        $callback->expects($this->once())
            ->method('callback')
            ->willReturn('fooValue')
        ;
        $container = $this->createContainerMock();
        $expectedService = $this->getMockBuilder(VirtualProxyInterface::class)
            ->getMock();

        $this->proxyFactory->expects($this->once())
            ->method('createProxy')
            ->willReturnCallback(
                function ($className, $initializer) use ($expectedService) {
                    $this->assertEquals('FooClass', $className, 'class name not match');

                    $wrappedInstance = null;
                    $result = $initializer(
                        $wrappedInstance,
                        $this->getMockBuilder(LazyLoadingInterface::class)->getMock()
                    );

                    $this->assertEquals('fooValue', $wrappedInstance, 'expected callback return value');
                    $this->assertTrue($result, 'initializer should return true');

                    return $expectedService;
                }
            )
        ;

        $result = $this->factory->__invoke($container, 'fooService', [$callback, 'callback']);

        $this->assertSame($expectedService, $result, 'service created not match the expected');
    }

    /**
     * @return ContainerInterface|MockObject
     */
    private function createContainerMock()
    {
        /** @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();

        return $container;
    }
}
