<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use Zend\ServiceManager\AbstractAliasedPluginManager;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendTest\ServiceManager\Asset\InvokableObject;

class AbstractAliasedPluginManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractAliasedPluginManager
     */
    private $pluginManager;

    public function setUp()
    {
        $config = [
            'factories' => [
                InvokableObject::class => InvokableFactory::class
            ],
            'aliases' => [
                'foo' => InvokableObject::class,
                'bar' => 'foo'
            ]
        ];

        $serviceLocator      = $this->getMock(ServiceLocatorInterface::class);
        $this->pluginManager = $this->getMockForAbstractClass(
            AbstractAliasedPluginManager::class,
            [$serviceLocator, $config]
        );
    }

    public function testCreateObjectWithAlias()
    {
        $object = $this->pluginManager->get('bar');
        $this->assertInstanceOf(InvokableObject::class, $object);
    }

    public function testCheckObjectExistanceWithAlias()
    {
        $this->assertTrue($this->pluginManager->has('bar'));
        $this->assertFalse($this->pluginManager->has('baz'));
    }
}