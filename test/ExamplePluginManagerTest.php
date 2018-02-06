<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager;

use PHPUnit\Framework\TestCase;
use Mxc\ServiceManager\ServiceManager;
use Mxc\ServiceManager\Test\CommonPluginManagerTrait;
use MxcTest\ServiceManager\TestAsset\InvokableObject;
use MxcTest\ServiceManager\TestAsset\V2v3PluginManager;

/**
 * Example test of using CommonPluginManagerTrait
 */
class ExamplePluginManagerTest extends TestCase
{
    use CommonPluginManagerTrait;

    protected function getPluginManager()
    {
        return new V2v3PluginManager(new ServiceManager());
    }

    protected function getV2InvalidPluginException()
    {
        return \RuntimeException::class;
    }

    protected function getInstanceOf()
    {
        return InvokableObject::class;
    }
}
