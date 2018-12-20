<?php
/**
 * @see       https://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-servicemanager/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ServiceManager\AbstractFactory\TestAsset;

class ClassWithPrivateConstructor
{
    private function __construct()
    {
    }
}
