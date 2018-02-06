<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager\AbstractFactory\TestAsset;

class ClassAcceptingConfigToConstructor
{
    public $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }
}
