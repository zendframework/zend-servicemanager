<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager\TestAsset;

use stdClass;
use Mxc\ServiceManager\Factory\InvokableFactory;
use Mxc\ServiceManager\ServiceManager;

class SimpleServiceManager extends ServiceManager
{
    protected $factories = [
        stdClass::class => InvokableFactory::class
    ];
}
