<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager\TestAsset;

use RuntimeException;
use Mxc\ServiceManager\AbstractPluginManager;

use function call_user_func;
use function is_callable;
use function sprintf;

class V2ValidationPluginManager extends AbstractPluginManager
{
    public $assertion;

    public function validatePlugin($plugin)
    {
        if (! is_callable($this->assertion)) {
            throw new RuntimeException(sprintf(
                '%s requires a callable $assertion property; not currently set',
                __CLASS__
            ));
        }

        call_user_func($this->assertion, $plugin);
    }
}
