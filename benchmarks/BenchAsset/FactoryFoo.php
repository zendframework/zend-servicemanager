<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcBench\ServiceManager\BenchAsset;

use Interop\Container\ContainerInterface;
use Mxc\ServiceManager\Factory\FactoryInterface;

class FactoryFoo implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Foo($options);
    }
}
