<?php
namespace ZendBench\ServiceManager\BenchAsset;

use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Interop\Container\ContainerInterface;

class AbstractFactoryFoo implements AbstractFactoryInterface
{
    public function canCreateServiceWithName(ContainerInterface $container, $requestedName)
    {
        return ($requestedName === 'foo');
    }

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if ($requestedName === 'foo') {
            return new Foo($options);
        }
        return false;
    }
}
