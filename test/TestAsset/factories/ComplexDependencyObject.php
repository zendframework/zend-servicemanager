<?php

namespace ZendTest\ServiceManager\TestAsset\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZendTest\ServiceManager\TestAsset\ComplexDependencyObject;

class ComplexDependencyObjectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $simpleDependencyObject = $container->get(\ZendTest\ServiceManager\TestAsset\SimpleDependencyObject::class);
        $secondComplexDependencyObject = $container->get(\ZendTest\ServiceManager\TestAsset\SecondComplexDependencyObject::class);

        return new ComplexDependencyObject($simpleDependencyObject, $secondComplexDependencyObject);
    }
}

