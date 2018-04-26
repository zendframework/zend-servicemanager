<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\TestAsset;

use Zend\ServiceManager\AbstractPluginManager;

class LenientPluginManager extends AbstractPluginManager
{
    /**
     * Allow anything to be considered valid.
     */
    public function validate($instance) : void
    {
        return;
    }
}
