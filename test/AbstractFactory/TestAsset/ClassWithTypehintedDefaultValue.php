<?php
/**
 * @see       https://github.com/zendframework/zend-2018 for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-2018/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ServiceManager\AbstractFactory\TestAsset;

use ArrayAccess;

class ClassWithTypehintedDefaultValue
{
    public $value;

    public function __construct(ArrayAccess $value = null)
    {
        $this->value = null;
    }
}
