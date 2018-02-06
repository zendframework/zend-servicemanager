<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcBench\ServiceManager\BenchAsset;

use Interop\Container\ContainerInterface;
use Mxc\ServiceManager\Factory\AbstractFactoryInterface;

class AbstractFactoryFoo implements AbstractFactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if ($requestedName === 'foo') {
            return new Foo($options);
        }
        return false;
    }

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return ($requestedName === 'foo');
    }
}
