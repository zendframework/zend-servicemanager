<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Tool;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Tool\ConfigDumper;
use ZendTest\ServiceManager\TestAsset\ClassDependingOnAnInterface;
use ZendTest\ServiceManager\TestAsset\DoubleDependencyObject;
use ZendTest\ServiceManager\TestAsset\FailingFactory;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\ObjectWithObjectScalarDependency;
use ZendTest\ServiceManager\TestAsset\ObjectWithScalarDependency;
use ZendTest\ServiceManager\TestAsset\SecondComplexDependencyObject;
use ZendTest\ServiceManager\TestAsset\SimpleDependencyObject;

class ConfigDumperTest extends TestCase
{
    /**
     * @var ConfigDumper
     */
    private $dumper;

    public function setUp()
    {
        $this->dumper = new ConfigDumper();
    }

    public function testCreateDependencyConfigExceptsIfClassNameIsNotString()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Class name must be a string, integer given');
        $this->dumper->createDependencyConfig([], 42);
    }

    public function testCreateDependencyConfigExceptsIfClassDoesNotExist()
    {
        $className = 'Dirk\Gentley\Holistic\Detective\Agency';
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Cannot find class or interface with name ' . $className);
        $this->dumper->createDependencyConfig([], $className);
    }

    public function testCreateDependencyConfigInvokableObjectReturnsEmptyArray()
    {
        $config = $this->dumper->createDependencyConfig([], InvokableObject::class);
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
        $config = $this->dumper->createDependencyConfig([], SimpleDependencyObject::class);
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

    public function testCreateDependencyConfigClassWithoutConstructorHandlesAsInvokable()
    {
        $expectedConfig = [
            ConfigAbstractFactory::class => [
                FailingFactory::class => [],
            ],
        ];
        $config = $this->dumper->createDependencyConfig([ConfigAbstractFactory::class => []], FailingFactory::class);
        self::assertEquals($expectedConfig, $config);
    }

    public function testCreateDependencyConfigWithoutTypeHintedParameterExcepts()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'Cannot create config for constructor argument "aName", '
            . 'it has no type hint, or non-class/interface type hint'
        );
        $config = $this->dumper->createDependencyConfig(
            [ConfigAbstractFactory::class => []],
            ObjectWithScalarDependency::class
        );
    }

    public function testCreateDependencyConfigWithContainerAndNoServiceWithoutTypeHintedParameterExcepts()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'Cannot create config for constructor argument "aName", '
            . 'it has no type hint, or non-class/interface type hint'
        );
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(ObjectWithScalarDependency::class)
            ->shouldBeCalled()
            ->willReturn(false);

        $dumper = new ConfigDumper($container->reveal());

        $config = $dumper->createDependencyConfig(
            [ConfigAbstractFactory::class => []],
            ObjectWithScalarDependency::class
        );
    }

    public function testCreateDependencyConfigWithContainerWithoutTypeHintedParameter()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(ObjectWithScalarDependency::class)
            ->shouldBeCalled()
            ->willReturn(true);

        $dumper = new ConfigDumper($container->reveal());

        $config = $dumper->createDependencyConfig(
            [ConfigAbstractFactory::class => []],
            ObjectWithObjectScalarDependency::class
        );

        self::assertEquals(
            [
                ConfigAbstractFactory::class => [
                    SimpleDependencyObject::class => [
                        InvokableObject::class,
                    ],
                    InvokableObject::class => [],
                    ObjectWithObjectScalarDependency::class => [
                        SimpleDependencyObject::class,
                        ObjectWithScalarDependency::class,
                    ],
                ]
            ],
            $config
        );
    }

    public function testCreateDependencyConfigWithoutTypeHintedParameterIgnoringUnresolved()
    {
        $config = $this->dumper->createDependencyConfig(
            [ConfigAbstractFactory::class => []],
            ObjectWithObjectScalarDependency::class,
            true
        );
        self::assertEquals(
            [
                ConfigAbstractFactory::class => [
                    SimpleDependencyObject::class => [
                        InvokableObject::class,
                    ],
                    InvokableObject::class => [],
                    ObjectWithObjectScalarDependency::class => [
                        SimpleDependencyObject::class,
                        ObjectWithScalarDependency::class,
                    ],
                ]
            ],
            $config
        );
    }

    public function testCreateDependencyConfigWorksWithExistingConfig()
    {
        $config = [
            ConfigAbstractFactory::class => [
                InvokableObject::class => [],
                SimpleDependencyObject::class => [
                    InvokableObject::class,
                ],
            ],
        ];

        self::assertEquals($config, $this->dumper->createDependencyConfig($config, SimpleDependencyObject::class));
    }

    public function testCreateDependencyConfigWorksWithMultipleDependenciesOfSameType()
    {
        $expectedConfig = [
            ConfigAbstractFactory::class => [
                DoubleDependencyObject::class => [
                    InvokableObject::class,
                    InvokableObject::class,
                ],
                InvokableObject::class => [],
            ],
        ];

        self::assertEquals($expectedConfig, $this->dumper->createDependencyConfig([], DoubleDependencyObject::class));
    }

    public function testCreateFactoryMappingsExceptsIfClassNameIsNotString()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Class name must be a string, integer given');
        $this->dumper->createFactoryMappings([], 42);
    }

    public function testCreateFactoryMappingsExceptsIfClassDoesNotExist()
    {
        $className = 'Dirk\Gentley\Holistic\Detective\Agency';
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Cannot find class or interface with name ' . $className);
        $this->dumper->createFactoryMappings([], $className);
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
        self::assertEquals($config, $this->dumper->createFactoryMappings($config, InvokableObject::class));
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
        self::assertEquals($expectedConfig, $this->dumper->createFactoryMappings([], InvokableObject::class));
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
        self::assertEquals($config, $this->dumper->createFactoryMappings($config, InvokableObject::class));
    }

    public function testCreateFactoryMappingsFromConfigReturnsIfNoConfigKey()
    {
        self::assertEquals([], $this->dumper->createFactoryMappingsFromConfig([]));
    }

    public function testCreateFactoryMappingsFromConfigExceptsWhenConfigNotArray()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'Config key for ' . ConfigAbstractFactory::class . ' should be an array, boolean given'
        );

        $this->dumper->createFactoryMappingsFromConfig(
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

        self::assertEquals($expectedConfig, $this->dumper->createFactoryMappingsFromConfig($config));
    }

    /**
     * @depends testCreateDependencyConfigSimpleDependencyReturnsCorrectly
     */
    public function testDumpConfigFileReturnsContentsForConfigFileUsingUsingClassNotationAndShortArrays(array $config)
    {
        $formatted = $this->dumper->dumpConfigFile($config);
        $this->assertContains(
            '<' . "?php\n/**\n * This file generated by Zend\ServiceManager\Tool\ConfigDumper.\n",
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

    public function testWillDumpConfigForClassDependingOnInterfaceButOmitInterfaceConfig()
    {
        $config = $this->dumper->createDependencyConfig([], ClassDependingOnAnInterface::class);
        self::assertEquals(
            [
                ConfigAbstractFactory::class => [
                    ClassDependingOnAnInterface::class => [
                        FactoryInterface::class,
                    ],
                ],
            ],
            $config
        );
    }
}
