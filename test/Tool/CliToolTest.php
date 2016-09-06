<?php
/**
 * Created by PhpStorm.
 * User: GeeH
 * Date: 06/09/2016
 * Time: 12:34
 */

namespace ZendTest\ServiceManager\Tool;


use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\Tool\CliTool;
use ZendTest\ServiceManager\TestAsset\FailingFactory;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\ObjectWithScalarDependency;
use ZendTest\ServiceManager\TestAsset\SimpleDependencyObject;

class CliToolTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptsIfClassNameIsNotString()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Class name must be a string, integer given');
        CliTool::handle([], 42);
    }

    public function testExceptsIfClassDoesNotExist()
    {
        $className = 'Dirk\Gentley\Holistic\Detective\Agency';
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Cannot find class with name ' . $className);
        CliTool::handle([], $className);
    }

    public function testInvokableObjectReturnsEmptyArray()
    {
        $config = CliTool::handle([], InvokableObject::class);
        self::assertEquals(
            [
                ConfigAbstractFactory::class => [
                    InvokableObject::class => []
                ]
            ],
            $config
        );
    }

    public function testSimpleDependencyReturnsCorrectly()
    {
        $config = CliTool::handle([], SimpleDependencyObject::class);
        self::assertEquals(
            [
                ConfigAbstractFactory::class => [
                    SimpleDependencyObject::class => [
                        InvokableObject::class,
                    ],
                    InvokableObject::class => [],
                ]
            ],
            $config
        );
    }

    public function testClassWithoutConstructorChangesNothing()
    {
        $config = CliTool::handle([ConfigAbstractFactory::class => []], FailingFactory::class);
        self::assertEquals([ConfigAbstractFactory::class => []], $config);
    }

    public function testWhatHappensWhenYouHaveNoTypeHint()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'Cannot create config for ' . ObjectWithScalarDependency::class . ', it has no type hints in constructor'
        );
        $config = CliTool::handle([ConfigAbstractFactory::class => []], ObjectWithScalarDependency::class);

    }

}
