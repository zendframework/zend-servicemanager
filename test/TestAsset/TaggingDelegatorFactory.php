<?php
/**
 * @link      https://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager\TestAsset;

use Interop\Container\ContainerInterface;
use Mxc\ServiceManager\Factory\DelegatorFactoryInterface;

class TaggingDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * {@inheritDoc}
     * @see \Mxc\ServiceManager\Factory\DelegatorFactoryInterface::__invoke()
     */
    public function __invoke(
        \Interop\Container\ContainerInterface $container,
        $name,
        callable $callback,
        array $options = null
    ) {
        $object = call_user_func($callback);
        $object->delegatorTag = true;
        return $object;
    }
}
