<?php
/**
 * @see       https://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-servicemanager/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ServiceManager\AbstractFactory;

use ArrayAccess;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ServiceManager\AbstractFactory\TestAsset\SampleInterface;

use function sprintf;

class ReflectionBasedAbstractFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function nonClassRequestedNames()
    {
        return [
            'non-class-string' => ['non-class-string'],
        ];
    }

    /**
     * @dataProvider nonClassRequestedNames
     */
    public function testCanCreateReturnsFalseForNonClassRequestedNames($requestedName)
    {
        $factory = new ReflectionBasedAbstractFactory();
        self::assertFalse($factory->canCreate($this->container->reveal(), $requestedName));
    }

    public function testCanCreateReturnsFalseWhenConstructorIsPrivate() : void
    {
        self::assertFalse(
            (new ReflectionBasedAbstractFactory())->canCreate(
                $this->container->reveal(),
                TestAsset\ClassWithPrivateConstructor::class
            ),
            'ReflectionBasedAbstractFactory should not be able to instantiate a class with a private constructor'
        );
    }

    public function testCanCreateReturnsTrueWhenClassHasNoConstructor() : void
    {
        self::assertTrue(
            (new ReflectionBasedAbstractFactory())->canCreate(
                $this->container->reveal(),
                TestAsset\ClassWithNoConstructor::class
            ),
            'ReflectionBasedAbstractFactory should be able to instantiate a class without a constructor'
        );
    }

    public function testFactoryInstantiatesClassDirectlyIfItHasNoConstructor()
    {
        $factory = new ReflectionBasedAbstractFactory();
        $instance = $factory($this->container->reveal(), TestAsset\ClassWithNoConstructor::class);
        self::assertInstanceOf(TestAsset\ClassWithNoConstructor::class, $instance);
    }

    public function testFactoryInstantiatesClassDirectlyIfConstructorHasNoArguments()
    {
        $factory = new ReflectionBasedAbstractFactory();
        $instance = $factory($this->container->reveal(), TestAsset\ClassWithEmptyConstructor::class);
        self::assertInstanceOf(TestAsset\ClassWithEmptyConstructor::class, $instance);
    }

    public function testFactoryRaisesExceptionWhenUnableToResolveATypeHintedService()
    {
        $this->container->has(TestAsset\SampleInterface::class)->willReturn(false);
        $this->container->has('config')->willReturn(false);
        $factory = new ReflectionBasedAbstractFactory();
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(sprintf(
            'Unable to create service "%s"; unable to resolve parameter "sample" using type hint "%s"',
            TestAsset\ClassWithTypeHintedConstructorParameter::class,
            TestAsset\SampleInterface::class
        ));
        $factory($this->container->reveal(), TestAsset\ClassWithTypeHintedConstructorParameter::class);
    }

    public function testFactoryRaisesExceptionForScalarParameters()
    {
        $factory = new ReflectionBasedAbstractFactory();
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(sprintf(
            'Unable to create service "%s"; unable to resolve parameter "foo" to a class, interface, or array type',
            TestAsset\ClassWithScalarParameters::class
        ));
        $factory($this->container->reveal(), TestAsset\ClassWithScalarParameters::class);
    }

    public function testFactoryInjectsConfigServiceForConfigArgumentsTypeHintedAsArray()
    {
        $config = ['foo' => 'bar'];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        $factory = new ReflectionBasedAbstractFactory();
        $instance = $factory($this->container->reveal(), TestAsset\ClassAcceptingConfigToConstructor::class);
        self::assertInstanceOf(TestAsset\ClassAcceptingConfigToConstructor::class, $instance);
        self::assertEquals($config, $instance->config);
    }

    public function testFactoryCanInjectKnownTypeHintedServices()
    {
        $sample = $this->prophesize(TestAsset\SampleInterface::class)->reveal();
        $this->container->has('config')->willReturn(false);
        $this->container->has(TestAsset\SampleInterface::class)->willReturn(true);
        $this->container->get(TestAsset\SampleInterface::class)->willReturn($sample);

        $factory = new ReflectionBasedAbstractFactory();
        $instance = $factory($this->container->reveal(), TestAsset\ClassWithTypeHintedConstructorParameter::class);
        self::assertInstanceOf(TestAsset\ClassWithTypeHintedConstructorParameter::class, $instance);
        self::assertSame($sample, $instance->sample);
    }

    public function testFactoryResolvesTypeHintsForServicesToWellKnownServiceNames()
    {
        $this->container->has('config')->willReturn(false);

        $validators = $this->prophesize(TestAsset\ValidatorPluginManager::class)->reveal();
        $this->container->has('ValidatorManager')->willReturn(true);
        $this->container->get('ValidatorManager')->willReturn($validators);

        $factory = new ReflectionBasedAbstractFactory([TestAsset\ValidatorPluginManager::class => 'ValidatorManager']);
        $instance = $factory(
            $this->container->reveal(),
            TestAsset\ClassAcceptingWellKnownServicesAsConstructorParameters::class
        );
        self::assertInstanceOf(
            TestAsset\ClassAcceptingWellKnownServicesAsConstructorParameters::class,
            $instance
        );
        self::assertSame($validators, $instance->validators);
    }

    public function testFactoryCanSupplyAMixOfParameterTypes()
    {
        $validators = $this->prophesize(TestAsset\ValidatorPluginManager::class)->reveal();
        $this->container->has('ValidatorManager')->willReturn(true);
        $this->container->get('ValidatorManager')->willReturn($validators);

        $sample = $this->prophesize(TestAsset\SampleInterface::class)->reveal();
        $this->container->has(TestAsset\SampleInterface::class)->willReturn(true);
        $this->container->get(TestAsset\SampleInterface::class)->willReturn($sample);

        $config = ['foo' => 'bar'];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        $factory = new ReflectionBasedAbstractFactory([TestAsset\ValidatorPluginManager::class => 'ValidatorManager']);
        $instance = $factory($this->container->reveal(), TestAsset\ClassWithMixedConstructorParameters::class);
        self::assertInstanceOf(TestAsset\ClassWithMixedConstructorParameters::class, $instance);

        self::assertEquals($config, $instance->config);
        self::assertEquals([], $instance->options);
        self::assertSame($sample, $instance->sample);
        self::assertSame($validators, $instance->validators);
    }

    public function testFactoryWillUseDefaultValueWhenPresentForScalarArgument()
    {
        $this->container->has('config')->willReturn(false);
        $factory = new ReflectionBasedAbstractFactory();
        $instance = $factory(
            $this->container->reveal(),
            TestAsset\ClassWithScalarDependencyDefiningDefaultValue::class
        );
        self::assertInstanceOf(TestAsset\ClassWithScalarDependencyDefiningDefaultValue::class, $instance);
        self::assertEquals('bar', $instance->foo);
    }

    /**
     * @see https://github.com/zendframework/zend-servicemanager/issues/239
     */
    public function testFactoryWillUseDefaultValueForTypeHintedArgument()
    {
        $this->container->has('config')->willReturn(false);
        $this->container->has(ArrayAccess::class)->willReturn(false);
        $factory = new ReflectionBasedAbstractFactory();
        $instance = $factory(
            $this->container->reveal(),
            TestAsset\ClassWithTypehintedDefaultValue::class
        );
        $this->assertInstanceOf(TestAsset\ClassWithTypehintedDefaultValue::class, $instance);
        $this->assertNull($instance->value);
    }

    public function testFactoryThrowsExceptionWhenCyclicDependencyDetectedForDecorators(): void
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setAlias(
            TestAsset\SampleInterface::class,
            TestAsset\DecoratorWithCyclicDependencyOnInterface::class
        );
        $serviceManager->addAbstractFactory(ReflectionBasedAbstractFactory::class);
        $serviceManager->setFactory(
            TestAsset\DecoratorWithCyclicDependencyOnInterface::class,
            ReflectionBasedAbstractFactory::class
        );

        $this->expectException(InvalidServiceException::class);
        $this->expectExceptionMessage(sprintf(
            'Unable to create service "%s"; a cyclic dependency was detected',
            TestAsset\SampleInterface::class
        ));
        $serviceManager->get(SampleInterface::class);
    }
}
