<?php

namespace ZendTest\ServiceManager\TestAsset;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class OffRoaderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $car = new Car();
        $car->classifier = 'I am a factory produced offroader.';
        return $car;
    }
}
