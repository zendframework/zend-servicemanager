<?php
/**
 * @link      https://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcBench\ServiceManager\BenchAsset;

use Interop\Container\ContainerInterface;
use Mxc\ServiceManager\Factory\DelegatorFactoryInterface;

class DelegatorFactoryFoo implements DelegatorFactoryInterface
{
    /**
     * {@inheritDoc}
     * @see \Mxc\ServiceManager\Factory\DelegatorFactoryInterface::__invoke()
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        return $callback($options);
    }
}
