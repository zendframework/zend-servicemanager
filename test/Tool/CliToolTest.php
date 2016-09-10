<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace ZendTest\ServiceManager\Tool;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\Tool\CliTool;
use ZendTest\ServiceManager\TestAsset\FailingFactory;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\ObjectWithScalarDependency;
use ZendTest\ServiceManager\TestAsset\SecondComplexDependencyObject;
use ZendTest\ServiceManager\TestAsset\SimpleDependencyObject;

class CliToolTest extends TestCase
{
    public function testCreateDependencyConfigExceptsIfClassNameIsNotString()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Class name must be a string, integer given');
        CliTool::createDependencyConfig([], 42);
    }

    public function testCreateDependencyConfigExceptsIfClassDoesNotExist()
    {
        $className = 'Dirk\Gentley\Holistic\Detective\Agency';
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Cannot find class with name ' . $className);
        CliTool::createDependencyConfig([], $className);
    }

    public function testCreateDependencyConfigInvokableObjectReturnsEmptyArray()
    {
        $config = CliTool::createDependencyConfig([], InvokableObject::class);
        self::assertEquals(
            [
                ConfigAbstractFactory::class => [
                    InvokableObject::class => []
                ]
            ],
            $config
        );
    }

    public function testCreateDependencyConfigSimpleDependencyReturnsCorrectly()
    {
        $config = CliTool::createDependencyConfig([], SimpleDependencyObject::class);
        self::assertEquals(
            [
                ConfigAbstractFactory::class => [
                    SimpleDependencyObject::class => [
                        InvokableObject::class,
                    ],
                    InvokableObject::class => [],
                ]
            ],
            $config
        );
        return $config;
    }

    public function testCreateDependencyConfigClassWithoutConstructorChangesNothing()
    {
        $config = CliTool::createDependencyConfig([ConfigAbstractFactory::class => []], FailingFactory::class);
        self::assertEquals([ConfigAbstractFactory::class => []], $config);
    }

    public function testCreateDependencyConfigWithoutTypeHintedParameterExcepts()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'Cannot create config for ' . ObjectWithScalarDependency::class . ', it has no type hints in constructor'
        );
        $config = CliTool::createDependencyConfig(
            [ConfigAbstractFactory::class => []],
            ObjectWithScalarDependency::class
        );
    }

    public function testCreateFactoryMappingsExceptsIfClassNameIsNotString()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Class name must be a string, integer given');
        CliTool::createFactoryMappings([], 42);
    }

    public function testCreateFactoryMappingsExceptsIfClassDoesNotExist()
    {
        $className = 'Dirk\Gentley\Holistic\Detective\Agency';
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Cannot find class with name ' . $className);
        CliTool::createFactoryMappings([], $className);
    }

    public function testCreateFactoryMappingsReturnsUnmodifiedArrayIfMappingExists()
    {
        $config = [
            'service_manager' => [
                'factories' => [
                    InvokableObject::class => ConfigAbstractFactory::class,
                ],
            ],
        ];
        self::assertEquals($config, CliTool::createFactoryMappings($config, InvokableObject::class));
    }

    public function testCreateFactoryMappingsAddsClassIfNotExists()
    {
        $expectedConfig = [
            'service_manager' => [
                'factories' => [
                    InvokableObject::class => ConfigAbstractFactory::class,
                ],
            ],
        ];
        self::assertEquals($expectedConfig, CliTool::createFactoryMappings([], InvokableObject::class));
    }

    public function testCreateFactoryMappingsIgnoresExistingsMappings()
    {
        $config = [
            'service_manager' => [
                'factories' => [
                    InvokableObject::class => 'SomeOtherExistingFactory',
                ],
            ],
        ];
        self::assertEquals($config, CliTool::createFactoryMappings($config, InvokableObject::class));
    }

    public function testCreateFactoryMappingsFromConfigReturnsIfNoConfigKey()
    {
        self::assertEquals([], CliTool::createFactoryMappingsFromConfig([]));
    }

    public function testCreateFactoryMappingsFromConfigExceptsWhenConfigNotArray()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'Config key for ' . ConfigAbstractFactory::class . ' should be an array, boolean given'
        );

        CliTool::createFactoryMappingsFromConfig(
            [
                ConfigAbstractFactory::class => true,
            ]
        );
    }

    public function testCreateFactoryMappingsFromConfigWithWorkingConfig()
    {
        $config = [
            ConfigAbstractFactory::class => [
                InvokableObject::class => [],
                SimpleDependencyObject::class => [
                    InvokableObject::class,
                ],
                SecondComplexDependencyObject::class => [
                    InvokableObject::class,
                ],
            ],
        ];

        $expectedConfig = [
            ConfigAbstractFactory::class => [
                InvokableObject::class => [],
                SimpleDependencyObject::class => [
                    InvokableObject::class,
                ],
                SecondComplexDependencyObject::class => [
                    InvokableObject::class,
                ],
            ],
            'service_manager' => [
                'factories' => [
                    InvokableObject::class => ConfigAbstractFactory::class,
                    SimpleDependencyObject::class => ConfigAbstractFactory::class,
                    SecondComplexDependencyObject::class => ConfigAbstractFactory::class,
                ],
            ],
        ];

        self::assertEquals($expectedConfig, CliTool::createFactoryMappingsFromConfig($config));
    }

    /**
     * @depends testCreateDependencyConfigSimpleDependencyReturnsCorrectly
     */
    public function testDumpConfigFileReturnsContentsForConfigFileUsingUsingClassNotationAndShortArrays(array $config)
    {
        $formatted = CliTool::dumpConfigFile($config);
        $this->assertContains(
            '<' . "?php\n/**\n * This file generated by Zend\ServiceManager\Tool\CliTool.\n",
            $formatted
        );

        $this->assertNotContains('array(', $formatted);
        $this->assertContains('::class', $formatted);

        $file = tempnam(sys_get_temp_dir(), 'ZSCLI');
        file_put_contents($file, $formatted);
        $test = include($file);
        unlink($file);

        $this->assertEquals($test, $config);
    }
}
