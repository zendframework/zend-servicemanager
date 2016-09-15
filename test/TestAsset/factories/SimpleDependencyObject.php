<?php

namespace ZendTest\ServiceManager\TestAsset;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZendTest\ServiceManager\TestAsset\SimpleDependencyObject;

class SimpleDependencyObjectFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return SimpleDependencyObject
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new SimpleDependencyObject($container->get(\ZendTest\ServiceManager\TestAsset\InvokableObject::class));
    }
}
