<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\TestAsset;

use Interop\Container\ContainerInterface;
use stdClass;
use Zend\ServiceManager\Initializer\InitializerInterface;

class SimpleInitializer implements InitializerInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(ContainerInterface $container, $instance)
    {
        if (! $instance instanceof stdClass) {
            return;
        }
        $instance->foo = 'bar';
    }
}
