<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\TestAsset;

use RuntimeException;
use Zend\ServiceManager\AbstractPluginManager;

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
