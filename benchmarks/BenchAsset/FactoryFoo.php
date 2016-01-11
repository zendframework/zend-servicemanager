<?php
namespace ZendBench\ServiceManager\BenchAsset;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class FactoryFoo implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Foo($options);
    }
}
