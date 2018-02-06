<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager\Tool;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Mxc\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Mxc\ServiceManager\Exception\InvalidArgumentException;
use Mxc\ServiceManager\Factory\FactoryInterface;
use Mxc\ServiceManager\Tool\ConfigDumper;
use MxcTest\ServiceManager\TestAsset\ClassDependingOnAnInterface;
use MxcTest\ServiceManager\TestAsset\DoubleDependencyObject;
use MxcTest\ServiceManager\TestAsset\FailingFactory;
use MxcTest\ServiceManager\TestAsset\InvokableObject;
use MxcTest\ServiceManager\TestAsset\ObjectWithObjectScalarDependency;
use MxcTest\ServiceManager\TestAsset\ObjectWithScalarDependency;
use MxcTest\ServiceManager\TestAsset\SecondComplexDependencyObject;
use MxcTest\ServiceManager\TestAsset\SimpleDependencyObject;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class name must be a string, integer given');
        $this->dumper->createDependencyConfig([], 42);
    }

    public function testCreateDependencyConfigExceptsIfClassDoesNotExist()
    {
        $className = 'Dirk\Gentley\Holistic\Detective\Agency';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find class or interface with name ' . $className);
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot create config for constructor argument "aName", '
            . 'it has no type hint, or non-class/interface type hint'
        );
        $this->dumper->createDependencyConfig(
            [ConfigAbstractFactory::class => []],
            ObjectWithScalarDependency::class
        );
    }

    public function testCreateDependencyConfigWithContainerAndNoServiceWithoutTypeHintedParameterExcepts()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot create config for constructor argument "aName", '
            . 'it has no type hint, or non-class/interface type hint'
        );
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(ObjectWithScalarDependency::class)
            ->shouldBeCalled()
            ->willReturn(false);

        $dumper = new ConfigDumper($container->reveal());

        $dumper->createDependencyConfig(
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class name must be a string, integer given');
        $this->dumper->createFactoryMappings([], 42);
    }

    public function testCreateFactoryMappingsExceptsIfClassDoesNotExist()
    {
        $className = 'Dirk\Gentley\Holistic\Detective\Agency';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find class or interface with name ' . $className);
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
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
        self::assertContains(
            '<' . "?php\n/**\n * This file generated by Mxc\ServiceManager\Tool\ConfigDumper.\n",
            $formatted
        );

        self::assertNotContains('array(', $formatted);
        self::assertContains('::class', $formatted);

        $file = tempnam(sys_get_temp_dir(), 'ZSCLI');
        file_put_contents($file, $formatted);
        $test = include $file;
        unlink($file);

        self::assertEquals($test, $config);
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
