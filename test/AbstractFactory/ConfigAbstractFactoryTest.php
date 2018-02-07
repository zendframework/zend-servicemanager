<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\AbstractFactory;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ServiceManager\TestAsset\ComplexDependencyObject;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\SecondComplexDependencyObject;
use ZendTest\ServiceManager\TestAsset\SimpleDependencyObject;

class ConfigAbstractFactoryTest extends TestCase
{

    public function testCanCreateReturnsTrueIfDependencyNotArrays()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            [
                ConfigAbstractFactory::class => 'Blancmange',
            ]
        );

        self::assertFalse($abstractFactory->canCreate($serviceManager, InvokableObject::class));

        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(
            'config',
            [
                ConfigAbstractFactory::class => [
                    InvokableObject::class => 42,
                ]
            ]
        );
        self::assertTrue($abstractFactory->canCreate($serviceManager, InvokableObject::class));

        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(
            'config',
            [
                ConfigAbstractFactory::class => [
                    InvokableObject::class => [
                        'Jabba',
                        'Gandalf',
                        'Blofeld',
                        42
                    ],
                ]
            ]
        );
        self::assertTrue($abstractFactory->canCreate($serviceManager, InvokableObject::class));
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

    public function testCanCreateReturnsTrueWhenConfigIsAnArrayObject()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            new ArrayObject([
                ConfigAbstractFactory::class => [
                    InvokableObject::class => [],
                ]
            ])
        );

        self::assertTrue($abstractFactory->canCreate($serviceManager, InvokableObject::class));
        self::assertFalse($abstractFactory->canCreate($serviceManager, ServiceManager::class));
    }

    public function testFactoryCanCreateInstancesWhenConfigIsAnArrayObject()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            new ArrayObject([
                ConfigAbstractFactory::class => [
                    InvokableObject::class => [],
                ]
            ])
        );

        self::assertInstanceOf(InvokableObject::class, $abstractFactory($serviceManager, InvokableObject::class));
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
                    SimpleDependencyObject::class => [
                        InvokableObject::class,
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
            'config',
            [
                ConfigAbstractFactory::class => [
                    InvokableObject::class => [],
                    SimpleDependencyObject::class => [
                        InvokableObject::class,
                    ],
                    ComplexDependencyObject::class => [
                        SimpleDependencyObject::class,
                        SecondComplexDependencyObject::class,
                    ],
                    SecondComplexDependencyObject::class => [
                        InvokableObject::class,
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

    public function testExceptsWhenConfigNotSet()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        self::expectException(ServiceNotCreatedException::class);
        self::expectExceptionMessage('Cannot find a config array in the container');

        $abstractFactory($serviceManager, 'Dirk_Gently');
    }

    public function testExceptsWhenConfigKeyNotSet()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', []);
        self::expectException(ServiceNotCreatedException::class);
        self::expectExceptionMessage('Cannot find a `' . ConfigAbstractFactory::class . '` key in the config array');

        $abstractFactory($serviceManager, 'Dirk_Gently');
    }

    public function testExceptsWhenConfigIsNotArray()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', 'Holistic');
        self::expectException(ServiceNotCreatedException::class);
        self::expectExceptionMessage('Config must be an array');

        $abstractFactory($serviceManager, 'Dirk_Gently');
    }

    public function testExceptsWhenServiceConfigIsNotArray()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            [
                ConfigAbstractFactory::class => 'Detective_Agency'
            ]
        );
        self::expectException(ServiceNotCreatedException::class);
        self::expectExceptionMessage('Dependencies config must exist and be an array');

        $abstractFactory($serviceManager, 'Dirk_Gently');
    }

    public function testExceptsWhenServiceConfigDoesNotExist()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            [
                ConfigAbstractFactory::class => [],
            ]
        );
        self::expectException(ServiceNotCreatedException::class);
        self::expectExceptionMessage('Dependencies config must exist and be an array');

        $abstractFactory($serviceManager, 'Dirk_Gently');
    }

    public function testExceptsWhenServiceConfigForRequestedNameIsNotArray()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            [
                ConfigAbstractFactory::class => [
                    'DirkGently' => 'Holistic',
                ],
            ]
        );
        self::expectException(ServiceNotCreatedException::class);
        self::expectExceptionMessage('Dependencies config must exist and be an array');

        $abstractFactory($serviceManager, 'Dirk_Gently');
    }

    public function testExceptsWhenServiceConfigForRequestedNameIsNotArrayOfStrings()
    {
        $abstractFactory = new ConfigAbstractFactory();
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            [
                ConfigAbstractFactory::class => [
                    'DirkGently' => [
                        'Holistic',
                        'Detective',
                        'Agency',
                        42
                    ],
                ],
            ]
        );
        self::expectException(ServiceNotCreatedException::class);
        self::expectExceptionMessage(
            'Service message must be an array of strings, ["string","string","string","integer"] given'
        );

        $abstractFactory($serviceManager, 'DirkGently');
    }
}
