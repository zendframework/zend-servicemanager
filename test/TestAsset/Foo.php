<?php
/**
 * @link      https://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager\TestAsset;

class Foo
{
    protected $options;
    public $initializerPresent = false;

    public function __construct($options = null)
    {
        $this->options = $options;
    }

    public function assertInitializer()
    {
        $this->initializerPresent = true;
    }
}
