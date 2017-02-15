<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Tool;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Tool\FactoryCreatorCommand;
use Zend\Stdlib\ConsoleHelper;
use ZendTest\ServiceManager\TestAsset\ObjectWithScalarDependency;
use ZendTest\ServiceManager\TestAsset\SimpleDependencyObject;

class FactoryCreatorCommandTest extends TestCase
{
    public function setUp()
    {
        $this->helper = $this->prophesize(ConsoleHelper::class);
        $this->command = new FactoryCreatorCommand(ConfigDumperCommand::class, $this->helper->reveal());
    }

    public function testEmitsHelpWhenNoArgumentsProvided()
    {
        $command = $this->command;
        $this->assertHelp();
        $this->assertEquals(0, $command([]));
    }

    public function assertHelp($stream = STDOUT)
    {
        $this->helper->writeLine(
            Argument::containingString('<info>Usage:</info>'),
            true,
            $stream
        )->shouldBeCalled();
    }

    public function helpArguments()
    {
        return [
            'short' => ['-h'],
            'long' => ['--help'],
            'literal' => ['help'],
        ];
    }

    /**
     * @dataProvider helpArguments
     */
    public function testEmitsHelpWhenHelpArgumentProvidedAsFirstArgument($argument)
    {
        $command = $this->command;
        $this->assertHelp();
        $this->assertEquals(0, $command([$argument]));
    }

    public function invalidArguments()
    {
        return [
            'string' => ['string'],
            'interface' => [FactoryInterface::class],
        ];
    }

    /**
     * @dataProvider invalidArguments
     */
    public function testEmitsErrorMessageIfArgumentIsNotAClass($argument)
    {
        $command = $this->command;
        $this->assertErrorRaised(sprintf('Class "%s" does not exist', $argument));
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$argument]));
    }

    public function assertErrorRaised($message)
    {
        $this->helper->writeErrorMessage(
            Argument::containingString($message)
        )->shouldBeCalled();
    }

    public function testEmitsErrorWhenUnableToCreateFactory()
    {
        $command = $this->command;
        $this->assertErrorRaised('Unable to create factory for "' . ObjectWithScalarDependency::class . '":');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([ObjectWithScalarDependency::class]));
    }

    public function testEmitsFactoryFileToStdoutWhenSuccessful()
    {
        $command = $this->command;
        $expected = file_get_contents(__DIR__ . '/../TestAsset/factories/SimpleDependencyObject.php');

        $this->helper->write($expected, false)->shouldBeCalled();
        $this->assertEquals(0, $command([SimpleDependencyObject::class]));
    }
}
