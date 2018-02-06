<?php
/**
 * @link      https://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\TestAsset;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

class TaggingDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * {@inheritDoc}
     * @see \Zend\ServiceManager\Factory\DelegatorFactoryInterface::__invoke()
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
