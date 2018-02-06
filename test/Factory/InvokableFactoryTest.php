<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager\Factory;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Mxc\ServiceManager\Factory\InvokableFactory;
use MxcTest\ServiceManager\TestAsset\InvokableObject;

/**
 * @covers \Mxc\ServiceManager\Factory\InvokableFactory
 */
class InvokableFactoryTest extends TestCase
{
    public function testCanCreateObject()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();
        $factory   = new InvokableFactory();

        $object = $factory($container, InvokableObject::class, ['foo' => 'bar']);

        self::assertInstanceOf(InvokableObject::class, $object);
        self::assertEquals(['foo' => 'bar'], $object->options);
    }
}
