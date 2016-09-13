<?php

namespace ZendTest\ServiceManager\TestAsset\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZendTest\ServiceManager\TestAsset\SimpleDependencyObject;

class SimpleDependencyObjectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $invokableObject = $container->get(\ZendTest\ServiceManager\TestAsset\InvokableObject::class);

        return new SimpleDependencyObject($invokableObject);
    }
}

