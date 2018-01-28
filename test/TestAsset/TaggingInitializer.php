<?php
/**
 * @link      https://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\TestAsset;

use Zend\ServiceManager\Initializer\InitializerInterface;

class TaggingInitializer implements InitializerInterface
{
    /**
     * {@inheritDoc}
     * @see \Zend\ServiceManager\TaggingInitializer\InitializerInterface::__invoke()
     */
    public function __invoke(\Interop\Container\ContainerInterface $container, $instance)
    {
        $instance->initializerPresent = true;
    }
}
