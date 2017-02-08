<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Test\CommonPluginManagerTrait;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\V2v3PluginManager;

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
