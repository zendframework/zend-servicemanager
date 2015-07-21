<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use stdClass;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\SimplePluginManager;

class AbstractPluginManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testInjectCreationContextInFactories()
    {
        $invokableFactory = $this->getMock(FactoryInterface::class);

        $config = [
            'factories' => [
                InvokableObject::class => $invokableFactory
            ]
        ];

        $serviceLocator = $this->getMock(ServiceLocatorInterface::class);
        $pluginManager  = new SimplePluginManager($serviceLocator, $config);

        $invokableFactory->expects($this->once())
                         ->method('__invoke')
                         ->with($serviceLocator, InvokableObject::class)
                         ->will($this->returnValue(new InvokableObject()));

        $object = $pluginManager->get(InvokableObject::class);

        $this->assertInstanceOf(InvokableObject::class, $object);
    }

    public function testValidateInstance()
    {
        $config = [
            'factories' => [
                InvokableObject::class => new InvokableFactory(),
                stdClass::class        => new InvokableFactory()
            ]
        ];

        $serviceLocator = $this->getMock(ServiceLocatorInterface::class);
        $pluginManager  = new SimplePluginManager($serviceLocator, $config);

        // Assert no exception is triggered because the plugin manager validate ObjectWithOptions
        $pluginManager->get(InvokableObject::class);

        // Assert it throws an exception for anything else
        $this->setExpectedException(InvalidServiceException::class);
        $pluginManager->get(stdClass::class);
    }
}
