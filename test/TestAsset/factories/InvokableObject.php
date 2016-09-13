<?php

namespace ZendTest\ServiceManager\TestAsset\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZendTest\ServiceManager\TestAsset\InvokableObject;

class InvokableObjectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new InvokableObject();
    }
}

