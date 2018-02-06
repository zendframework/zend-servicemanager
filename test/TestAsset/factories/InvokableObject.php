<?php

namespace MxcTest\ServiceManager\TestAsset;

use Interop\Container\ContainerInterface;
use Mxc\ServiceManager\Factory\FactoryInterface;
use MxcTest\ServiceManager\TestAsset\InvokableObject;

class InvokableObjectFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return InvokableObject
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new InvokableObject();
    }
}
