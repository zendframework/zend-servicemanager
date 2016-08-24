<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\AbstractFactory;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Proxy\LazyServiceFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ServiceManager\TestAsset\ComplexDependencyObject;
use ZendTest\ServiceManager\TestAsset\FailingFactory;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\SimpleDependencyObject;

class ConfigAbstractFactoryTest extends \PHPUnit_Framework_TestCase
{

    public function testCanCreateShortCircuits()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();

        self::assertFalse($abstractFactory->canCreate($serviceManager, 'MarcoSucks'));
    }

    public function testCanCreate()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            [
                ConfigAbstractFactory::class => [
                    InvokableObject::class => [],
                ]
            ]
        );

        self::assertTrue($abstractFactory->canCreate($serviceManager, InvokableObject::class));
        self::assertFalse($abstractFactory->canCreate($serviceManager, ServiceManager::class));
    }

    public function testInvokeWithInvokableClass()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            [
                ConfigAbstractFactory::class => [
                    InvokableObject::class => [],
                ]
            ]
        );

        self::assertInstanceOf(InvokableObject::class, $abstractFactory($serviceManager, InvokableObject::class));
    }

    public function testInvokeWithSimpleArguments()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            [
                ConfigAbstractFactory::class => [
                    InvokableObject::class => [],
                    FailingFactory::class => [],
                    SimpleDependencyObject::class => [
                        InvokableObject::class,
                        FailingFactory::class,
                    ],
                ]
            ]
        );
        $serviceManager->addAbstractFactory($abstractFactory);

        self::assertInstanceOf(
            SimpleDependencyObject::class,
            $abstractFactory($serviceManager, SimpleDependencyObject::class)
        );
    }

    public function testInvokeWithComplexArguments()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            LazyLoadingValueHolderFactory::class . '.config',
            [
                'Proxies' => 'Suck',
            ]
        );
        $serviceManager->setService(
            'config',
            [
                ConfigAbstractFactory::class => [
                    InvokableObject::class => [],
                    FailingFactory::class => [],
                    SimpleDependencyObject::class => [
                        InvokableObject::class,
                        FailingFactory::class,
                    ],
                    LazyLoadingValueHolderFactory::class => [],
                    ComplexDependencyObject::class => [
                        SimpleDependencyObject::class,
                        LazyServiceFactory::class,
                    ],
                    LazyServiceFactory::class => [
                        LazyLoadingValueHolderFactory::class,
                        LazyLoadingValueHolderFactory::class . '.config',
                    ],
                ]
            ]
        );
        $serviceManager->addAbstractFactory($abstractFactory);

        self::assertInstanceOf(
            ComplexDependencyObject::class,
            $abstractFactory($serviceManager, ComplexDependencyObject::class)
        );
    }
}
