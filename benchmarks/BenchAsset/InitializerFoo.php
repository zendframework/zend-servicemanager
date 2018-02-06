<?php
/**
 * @link      https://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcBench\ServiceManager\BenchAsset;

use Mxc\ServiceManager\Initializer\InitializerInterface;

class InitializerFoo implements InitializerInterface
{
    protected $options;

    /**
     * {@inheritDoc}
     * @see \Mxc\ServiceManager\Initializer\InitializerInterface::__invoke()
     */
    public function __invoke(\Interop\Container\ContainerInterface $container, $instance)
    {
    }

    public function __construct($options = null)
    {
        $this->options = $options;
    }
}
