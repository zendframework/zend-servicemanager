<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendBench\ServiceManager\BenchAsset;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

class DelegatorFactoryFoo implements DelegatorFactoryInterface
{
    /**
     * {@inheritDoc}
     * @see \Zend\ServiceManager\Factory\DelegatorFactoryInterface::__invoke()
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        $foo = call_user_func($callback);
        return $foo;
    }
}
