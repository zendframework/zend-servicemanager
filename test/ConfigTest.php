<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class ConfigTest extends TestCase
{
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
        $services->withConfig($expected)->willReturn('CALLED');

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
