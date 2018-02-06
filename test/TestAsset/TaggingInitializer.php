<?php
/**
 * @link      https://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager\TestAsset;

use Mxc\ServiceManager\Initializer\InitializerInterface;

class TaggingInitializer implements InitializerInterface
{
    /**
     * {@inheritDoc}
     * @see \Mxc\ServiceManager\TaggingInitializer\InitializerInterface::__invoke()
     */
    public function __invoke(\Interop\Container\ContainerInterface $container, $instance)
    {
        $instance->initializerPresent = true;
    }
}
