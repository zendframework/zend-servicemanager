<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

/**
 * @covers Zend\ServiceManager\Config
 */
class ConfigTest extends TestCase
{
    public function testMergeArrays()
    {
        $config = [
            'invokables' => [
                'foo' => TestAsset\InvokableObject::class,
            ],
            'delegators' => [
                'foo' => [
                    TestAsset\PreDelegator::class,
                ]
            ],
            'factories' => [
                'service' => TestAsset\FactoryObject::class,
            ],
        ];

        $configuration = new TestAsset\ExtendedConfig($config);
        $result = $configuration->toArray();

        $expected = [
            'invokables' => [
                'foo' => TestAsset\InvokableObject::class,
                TestAsset\InvokableObject::class => TestAsset\InvokableObject::class,
            ],
            'delegators' => [
                'foo' => [
                    TestAsset\InvokableObject::class,
                    TestAsset\PreDelegator::class,
                ],
            ],
            'factories' => [
                'service' => TestAsset\FactoryObject::class,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testPassesKnownServiceConfigKeysToServiceManagerWithConfigMethod()
    {
        $expected = [
            'abstract_factories' => [
                __CLASS__,
                __NAMESPACE__,
            ],
            'aliases' => [
                'foo' => __CLASS__,
                'bar' => __NAMESPACE__,
            ],
            'delegators' => [
                'foo' => [
                    __CLASS__,
                    __NAMESPACE__,
                ]
            ],
            'factories' => [
                'foo' => __CLASS__,
                'bar' => __NAMESPACE__,
            ],
            'initializers' => [
                __CLASS__,
                __NAMESPACE__,
            ],
            'invokables' => [
                'foo' => __CLASS__,
                'bar' => __NAMESPACE__,
            ],
            'lazy_services' => [
                'class_map' => [
                    __CLASS__     => __CLASS__,
                    __NAMESPACE__ => __NAMESPACE__,
                ],
            ],
            'services' => [
                'foo' => $this,
            ],
            'shared' => [
                __CLASS__     => true,
                __NAMESPACE__ => false,
            ],
        ];

        $config = $expected + [
            'foo' => 'bar',
            'baz' => 'bat',
        ];

        $services = $this->prophesize(ServiceManager::class);
        $services->configure($expected)->willReturn('CALLED');

        $configuration = new Config($config);
        $this->assertEquals('CALLED', $configuration->configureServiceManager($services->reveal()));

        return [
            'array'  => $expected,
            'config' => $configuration,
        ];
    }

    /**
     * @depends testPassesKnownServiceConfigKeysToServiceManagerWithConfigMethod
     */
    public function testToArrayReturnsConfiguration($dependencies)
    {
        $configuration  = $dependencies['array'];
        $configInstance = $dependencies['config'];
        $this->assertSame($configuration, $configInstance->toArray());
    }
}
