<?php

namespace MxcTest\ServiceManager\TestAsset;

use Interop\Container\ContainerInterface;
use Mxc\ServiceManager\Factory\FactoryInterface;
use MxcTest\ServiceManager\TestAsset\ComplexDependencyObject;

class ComplexDependencyObjectFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return ComplexDependencyObject
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new ComplexDependencyObject(
            $container->get(\MxcTest\ServiceManager\TestAsset\SimpleDependencyObject::class),
            $container->get(\MxcTest\ServiceManager\TestAsset\SecondComplexDependencyObject::class)
        );
    }
}
