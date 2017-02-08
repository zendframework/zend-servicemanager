<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Tool;

use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\Tool\FactoryCreator;
use ZendTest\ServiceManager\TestAsset\ComplexDependencyObject;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\SimpleDependencyObject;

class FactoryCreatorTest extends TestCase
{
    /**
     * @var FactoryCreator
     */
    private $factoryCreator;

    /**
     * @internal param FactoryCreator $factoryCreator
     */
    public function setUp()
    {
        $this->factoryCreator = new FactoryCreator();
    }

    public function testCreateFactoryCreatesForInvokable()
    {
        $className = InvokableObject::class;
        $factory = file_get_contents(__DIR__ . '/../TestAsset/factories/InvokableObject.php');

        self::assertEquals($factory, $this->factoryCreator->createFactory($className));
    }

    public function testCreateFactoryCreatesForSimpleDependencies()
    {
        $className = SimpleDependencyObject::class;
        $factory = file_get_contents(__DIR__. '/../TestAsset/factories/SimpleDependencyObject.php');

        self::assertEquals($factory, $this->factoryCreator->createFactory($className));
    }

    public function testCreateFactoryCreatesForComplexDependencies()
    {
        $className = ComplexDependencyObject::class;
        $factory = file_get_contents(__DIR__. '/../TestAsset/factories/ComplexDependencyObject.php');

        self::assertEquals($factory, $this->factoryCreator->createFactory($className));
    }
}
