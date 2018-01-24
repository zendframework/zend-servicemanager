<?php
/**
 * @link      https://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendBench\ServiceManager\BenchAsset;

use Zend\ServiceManager\Initializer\InitializerInterface;

class InitializerFoo implements InitializerInterface
{
    protected $options;

    /**
     * {@inheritDoc}
     * @see \Zend\ServiceManager\Initializer\InitializerInterface::__invoke()
     */
    public function __invoke(\Interop\Container\ContainerInterface $container, $instance)
    {
    }

    public function __construct($options = null)
    {
        $this->options = $options;
    }
}
