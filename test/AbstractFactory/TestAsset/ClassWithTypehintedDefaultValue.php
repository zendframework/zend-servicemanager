<?php
/**
 * @see       https://github.com/Mxcframework/Mxc-2018 for the canonical source repository
 * @copyright Copyright (c) 2018 Mxc Technologies USA Inc. (https://www.Mxc.com)
 * @license   https://github.com/Mxcframework/Mxc-2018/blob/master/LICENSE.md New BSD License
 */

namespace MxcTest\ServiceManager\AbstractFactory\TestAsset;

use ArrayAccess;

class ClassWithTypehintedDefaultValue
{
    public $value;

    public function __construct(ArrayAccess $value = null)
    {
        $this->value = null;
    }
}
