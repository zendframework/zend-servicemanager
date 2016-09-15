<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\AbstractFactory\TestAsset;

class ClassWithScalarParameters
{
    public $foo = 'foo';
    public $bar = 'bar';

    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
