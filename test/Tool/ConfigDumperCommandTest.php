<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Tool;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Zend\ServiceManager\Tool\ConfigDumperCommand;
use Zend\Stdlib\ConsoleHelper;
use ZendTest\ServiceManager\TestAsset\InvokableObject;
use ZendTest\ServiceManager\TestAsset\ObjectWithScalarDependency;
use ZendTest\ServiceManager\TestAsset\SimpleDependencyObject;

class ConfigDumperCommandTest extends TestCase
{
    public function setUp()
    {
        $this->helper = $this->prophesize(ConsoleHelper::class);
        $this->command = new ConfigDumperCommand(ConfigDumperCommand::class, $this->helper->reveal());
    }

    public function assertHelp($stream = STDOUT)
    {
        $this->helper->writeLine(
            Argument::containingString('<info>Usage:</info>'),
            true,
            $stream
        )->shouldBeCalled();
    }

    public function assertErrorRaised($message)
    {
        $this->helper->writeErrorMessage(
            Argument::containingString($message)
        )->shouldBeCalled();
    }

    public function testEmitsHelpWhenNoArgumentsProvided()
    {
        $command = $this->command;
        $this->assertHelp();
        $this->assertEquals(0, $command([]));
    }

    public function helpArguments()
    {
        return [
            'short'   => ['-h'],
            'long'    => ['--help'],
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

    public function testEmitsErrorWhenTooFewArgumentsPresent()
    {
        $command = $this->command;
        $this->assertErrorRaised('Missing class name');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command(['foo']));
    }

    public function testEmitsErrorWhenConfigurationFileNotFound()
    {
        $command = $this->command;
        $this->assertErrorRaised('Cannot find configuration file at path "foo"');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command(['foo', 'Not\A\Real\Class']));
    }

    public function testEmitsErrorWhenConfigurationFileDoesNotReturnArray()
    {
        $command = $this->command;
        $config = realpath(__DIR__ . '/../TestAsset/config/invalid.config.php');
        $this->assertErrorRaised('Configuration at path "' . $config . '" does not return an array.');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, 'Not\A\Real\Class']));
    }

    public function testEmitsErrorWhenClassDoesNotExist()
    {
        $command = $this->command;
        $config = realpath(__DIR__ . '/../TestAsset/config/test.config.php');
        $this->assertErrorRaised('Class "Not\\A\\Real\\Class" does not exist or could not be autoloaded.');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, 'Not\A\Real\Class']));
    }

    public function testEmitsErrorWhenUnableToCreateConfiguration()
    {
        $command = $this->command;
        $config = realpath(__DIR__ . '/../TestAsset/config/test.config.php');
        $this->assertErrorRaised('Unable to create config for "' . ObjectWithScalarDependency::class . '":');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, ObjectWithScalarDependency::class]));
    }

    public function testEmitsConfigFileToStdoutWhenSuccessful()
    {
        $command = $this->command;
        $config = realpath(__DIR__ . '/../TestAsset/config/test.config.php');

        $this->helper->write(Argument::that(function ($config) {
            if (! strstr($config, 'return [')) {
                return false;
            }

            if (! strstr($config, SimpleDependencyObject::class . '::class => [')) {
                return false;
            }

            if (! strstr($config, InvokableObject::class . '::class => [')) {
                return false;
            }

            return true;
        }), false)->shouldBeCalled();

        $this->assertEquals(0, $command([$config, SimpleDependencyObject::class]));
    }
}
