<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\AbstractFactory;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class ReflectionBasedAbstractFactoryTest extends TestCase
{
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
        $this->assertFalse($factory->canCreate($this->container->reveal(), $requestedName));
    }

    public function testFactoryInstantiatesClassDirectlyIfItHasNoConstructor()
    {
        $factory = new ReflectionBasedAbstractFactory();
        $instance= $factory($this->container->reveal(), TestAsset\ClassWithNoConstructor::class);
        $this->assertInstanceOf(TestAsset\ClassWithNoConstructor::class, $instance);
    }

    public function testFactoryInstantiatesClassDirectlyIfConstructorHasNoArguments()
    {
        $factory = new ReflectionBasedAbstractFactory();
        $instance= $factory($this->container->reveal(), TestAsset\ClassWithEmptyConstructor::class);
        $this->assertInstanceOf(TestAsset\ClassWithEmptyConstructor::class, $instance);
    }

    public function testFactoryRaisesExceptionWhenUnableToResolveATypeHintedService()
    {
        $this->container->has(TestAsset\SampleInterface::class)->willReturn(false);
        $this->container->has('config')->willReturn(false);
        $factory = new ReflectionBasedAbstractFactory();
        $this->setExpectedException(
            ServiceNotFoundException::class,
            sprintf(
                'Unable to create controller "%s"; unable to resolve parameter "sample" using type hint "%s"',
                TestAsset\ClassWithTypeHintedConstructorParameter::class,
                TestAsset\SampleInterface::class
            )
        );
        $factory($this->container->reveal(), TestAsset\ClassWithTypeHintedConstructorParameter::class);
    }

    public function testFactoryPassesNullForScalarParameters()
    {
        $factory = new ReflectionBasedAbstractFactory();
        $instance= $factory($this->container->reveal(), TestAsset\ClassWithScalarParameters::class);
        $this->assertInstanceOf(TestAsset\ClassWithScalarParameters::class, $instance);
        $this->assertNull($instance->foo);
        $this->assertNull($instance->bar);
    }

    public function testFactoryInjectsConfigServiceForConfigArgumentsTypeHintedAsArray()
    {
        $config = ['foo' => 'bar'];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        $factory = new ReflectionBasedAbstractFactory();
        $instance = $factory($this->container->reveal(), TestAsset\ClassAcceptingConfigToConstructor::class);
        $this->assertInstanceOf(TestAsset\ClassAcceptingConfigToConstructor::class, $instance);
        $this->assertEquals($config, $instance->config);
    }

    public function testFactoryCanInjectKnownTypeHintedServices()
    {
        $sample = $this->prophesize(TestAsset\SampleInterface::class)->reveal();
        $this->container->has('config')->willReturn(false);
        $this->container->has(TestAsset\SampleInterface::class)->willReturn(true);
        $this->container->get(TestAsset\SampleInterface::class)->willReturn($sample);

        $factory = new ReflectionBasedAbstractFactory();
        $instance = $factory($this->container->reveal(), TestAsset\ClassWithTypeHintedConstructorParameter::class);
        $this->assertInstanceOf(TestAsset\ClassWithTypeHintedConstructorParameter::class, $instance);
        $this->assertSame($sample, $instance->sample);
    }

    public function testFactoryResolvesTypeHintsForServicesToWellKnownServiceNames()
    {
        $this->container->has('config')->willReturn(false);

        $validators = $this->prophesize(TestAsset\ValidatorPluginManager::class)->reveal();
        $this->container->has('ValidatorManager')->willReturn(true);
        $this->container->get('ValidatorManager')->willReturn($validators);

        $factory = new ReflectionBasedAbstractFactory([TestAsset\ValidatorPluginManager::class => 'ValidatorManager']);
        $instance= $factory(
            $this->container->reveal(),
            TestAsset\ClassAcceptingWellKnownServicesAsConstructorParameters::class
        );
        $this->assertInstanceOf(
            TestAsset\ClassAcceptingWellKnownServicesAsConstructorParameters::class,
            $instance
        );
        $this->assertSame($validators, $instance->validators);
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
        $instance= $factory($this->container->reveal(), TestAsset\ClassWithMixedConstructorParameters::class);
        $this->assertInstanceOf(TestAsset\ClassWithMixedConstructorParameters::class, $instance);

        $this->assertEquals($config, $instance->config);
        $this->assertNull($instance->foo);
        $this->assertEquals([], $instance->options);
        $this->assertSame($sample, $instance->sample);
        $this->assertSame($validators, $instance->validators);
    }
}
