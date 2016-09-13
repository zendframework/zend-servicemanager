<?php
/**
 * Created by PhpStorm.
 * User: GeeH
 * Date: 12/09/2016
 * Time: 16:29
 */

namespace ZendTest\ServiceManager\Tool;


use Zend\ServiceManager\Tool\FactoryCreator;
use ZendTest\ServiceManager\TestAsset\ComplexDependencyObject;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\SimpleDependencyObject;


class FactoryCreatorTest extends \PHPUnit_Framework_TestCase
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
