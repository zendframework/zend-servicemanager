<?php

namespace ZendTest\ServiceManager\TestAsset;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZendTest\ServiceManager\TestAsset\ComplexDependencyObject;

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
            $container->get(\ZendTest\ServiceManager\TestAsset\SimpleDependencyObject::class),
            $container->get(\ZendTest\ServiceManager\TestAsset\SecondComplexDependencyObject::class)
        );
    }
}
