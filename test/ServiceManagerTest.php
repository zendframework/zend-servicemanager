<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use DateTime;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Initializer\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ServiceManager\TestAsset\FailingAbstractFactory;
use ZendTest\ServiceManager\TestAsset\FailingFactory;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\SimpleAbstractFactory;
use ZendTest\ServiceManager\TestAsset\SimpleServiceManager;

/**
 * @covers \Zend\ServiceManager\ServiceManager
 */
class ServiceManagerTest extends TestCase
{
    use CommonServiceLocatorBehaviorsTrait;

    public function createContainer(array $config = [])
    {
        $this->creationContext = new ServiceManager($config);
        return $this->creationContext;
    }

    public function testConfigurationCanBeMerged()
    {
        $serviceManager = new SimpleServiceManager([
            'factories' => [
                DateTime::class => InvokableFactory::class
            ]
        ]);

        $this->assertTrue($serviceManager->has(DateTime::class));
        // stdClass service is inlined in SimpleServiceManager
        $this->assertTrue($serviceManager->has(stdClass::class));
    }

    public function testConfigurationTakesPrecedenceWhenMerged()
    {
        $factory = $this->getMock(FactoryInterface::class);

        $factory->expects($this->once())->method('__invoke');

        $serviceManager = new SimpleServiceManager([
            'factories' => [
                stdClass::class => $factory
            ]
        ]);

        $serviceManager->get(stdClass::class);
    }

    /**
     * @covers \Zend\ServiceManager\ServiceManager::doCreate
     * @covers \Zend\ServiceManager\ServiceManager::createDelegatorFromName
     */
    public function testCanWrapCreationInDelegators()
    {
        $config = [
            'option' => 'OPTIONED',
        ];
        $serviceManager = new ServiceManager([
            'services'  => [
                'config' => $config,
            ],
            'factories' => [
                stdClass::class => InvokableFactory::class,
            ],
            'delegators' => [
                stdClass::class => [
                    TestAsset\PreDelegator::class,
                    function ($container, $name, $callback) {
                        $instance = $callback();
                        $instance->foo = 'bar';
                        return $instance;
                    },
                ],
            ],
        ]);

        $instance = $serviceManager->get(stdClass::class);
        $this->assertTrue(isset($instance->option), 'Delegator-injected option was not found');
        $this->assertEquals(
            $config['option'],
            $instance->option,
            'Delegator-injected option does not match configuration'
        );
        $this->assertEquals('bar', $instance->foo);
    }
}
