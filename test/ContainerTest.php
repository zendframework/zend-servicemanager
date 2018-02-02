<?php
/**
 * @see       https://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-servicemanager/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ServiceManager;

use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\ServiceManager;

class ContainerTest extends TestCase
{
    public function config()
    {
        yield 'factories' => [['factories' => ['service' => TestAsset\SampleFactory::class]]];
        yield 'invokables' => [['invokables' => ['service' => TestAsset\InvokableObject::class]]];
        yield 'aliases-invokables' => [
            [
                'aliases' => ['service' => TestAsset\InvokableObject::class],
                'invokables' => [TestAsset\InvokableObject::class => TestAsset\InvokableObject::class],
            ],
        ];
        yield 'aliases-factories' => [
            [
                'aliases' => ['service' => TestAsset\InvokableObject::class],
                'factories' => [TestAsset\InvokableObject::class => TestAsset\SampleFactory::class],
            ],
        ];
    }

    /**
     * @dataProvider config
     */
    public function testIsSharedByDefault(array $config)
    {
        $container = $this->createContainer($config);

        $service1 = $container->get('service');
        $service2 = $container->get('service');

        $this->assertSame($service1, $service2);
    }

    /**
     * @dataProvider config
     */
    public function testCanDisableSharedByDefault(array $config)
    {
        $container = $this->createContainer(array_merge($config, [
            'shared_by_default' => false,
        ]));

        $service1 = $container->get('service');
        $service2 = $container->get('service');

        $this->assertNotSame($service1, $service2);
    }

    /**
     * @dataProvider config
     */
    public function testCanDisableSharedForSingleService(array $config)
    {
        $container = $this->createContainer(array_merge($config, [
            'shared' => [
                'service' => false,
            ],
        ]));

        $service1 = $container->get('service');
        $service2 = $container->get('service');

        $this->assertNotSame($service1, $service2);
    }

    /**
     * @dataProvider config
     */
    public function testCanEnableSharedForSingleService(array $config)
    {
        $container = $this->createContainer(array_merge($config, [
            'shared_by_default' => false,
            'shared' => [
                'service' => true,
            ],
        ]));

        $service1 = $container->get('service');
        $service2 = $container->get('service');

        $this->assertSame($service1, $service2);
    }

    private function createContainer(array $config)
    {
        return new ServiceManager($config);
    }
}
