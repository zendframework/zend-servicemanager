<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager\Tool;

use PHPUnit\Framework\TestCase;
use Mxc\ServiceManager\Tool\FactoryCreator;
use MxcTest\ServiceManager\TestAsset\ComplexDependencyObject;
use MxcTest\ServiceManager\TestAsset\InvokableObject;
use MxcTest\ServiceManager\TestAsset\SimpleDependencyObject;

use function file_get_contents;

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
