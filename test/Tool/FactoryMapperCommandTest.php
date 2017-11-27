<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Tool;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\Tool\FactoryMapperCommand;
use Zend\Stdlib\ConsoleHelper;
use ZendTest\ServiceManager\TestAsset\SimpleDependencyObject;

class FactoryMapperCommandTest extends TestCase
{
    public function setUp()
    {
        $this->configDir = vfsStream::setup('project');
        $this->helper = $this->prophesize(ConsoleHelper::class);
        $this->command = new FactoryMapperCommand(FactoryMapperCommand::class, $this->helper->reveal());
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
        $this->assertErrorRaised('Missing arguments');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command(['foo']));
    }

    public function testEmitsErrorWhenConfigFileDoesNotReturnAnArray()
    {
        $command = $this->command;
        vfsStream::newFile('config/invalid.config.php')
            ->at($this->configDir)
            ->setContent('<' . "?php\n// invalid");
        $config = vfsStream::url('project/config/invalid.config.php');

        $this->assertErrorRaised('Configuration at path "' . $config . '" does not return an array.');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, 'Not\A\Real\Class', 'Not\A\Real\Factory']));
    }

    public function testEmitsErrorWhenUnableToCreateConfigFile()
    {
        $command = $this->command;
        vfsStream::newDirectory('config', 0551)
            ->at($this->configDir);
        $config = vfsStream::url('project/config/invalid.config.php');

        $this->assertErrorRaised(
            'Configuration at path "' . $config . '" cannot be created; directory is not writable.'
        );
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, 'Not\A\Real\Class', 'Not\A\Real\Factory']));
    }

    public function testEmitsErrorWhenClassIsNotFound()
    {
        $command = $this->command;
        vfsStream::newFile('config/test.config.php')
            ->at($this->configDir)
            ->setContent(file_get_contents(realpath(__DIR__ . '/../TestAsset/config/test.config.php')));
        $config = vfsStream::url('project/config/test.config.php');
        $this->assertErrorRaised('Class "Not\\A\\Real\\Class" does not exist or could not be autoloaded.');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, 'Not\A\Real\Class', 'Not\A\Real\Factory']));
    }

    public function testEmitsErrorWhenFactoryIsNotFound()
    {
        $command = $this->command;
        vfsStream::newFile('config/test.config.php')
            ->at($this->configDir)
            ->setContent(file_get_contents(realpath(__DIR__ . '/../TestAsset/config/test.config.php')));
        $config = vfsStream::url('project/config/test.config.php');
        $this->assertErrorRaised('Factory "Not\\A\\Real\\Factory" does not exist or could not be autoloaded.');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, SimpleDependencyObject::class, 'Not\A\Real\Factory']));
    }

    public function testEmitsErrorWhenConfigUnderKeyIsMalformed()
    {
        $command = $this->command;
        vfsStream::newFile('config/test.config.php')
            ->at($this->configDir)
            ->setContent('<' . "?php\nreturn [\n    'foo' => false,\n];");
        $config = vfsStream::url('project/config/test.config.php');
        $this->assertErrorRaised(sprintf(
            'Config file "%s" contains the key "%s", but it is not an array; aborting.',
            $config,
            'foo'
        ));
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, SimpleDependencyObject::class, InvokableFactory::class, 'foo']));
    }

    public function testEmitsErrorWhenFactoriesValueUnderKeyIsMalformed()
    {
        $command = $this->command;
        vfsStream::newFile('config/test.config.php')
            ->at($this->configDir)
            ->setContent('<' . "?php\nreturn [\n    'foo' => [\n        'factories' => false,\n    ],\n];");
        $config = vfsStream::url('project/config/test.config.php');
        $this->assertErrorRaised(
            'Configuration at key "foo" contains factories configuration, but it is not an array.'
        );
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, SimpleDependencyObject::class, InvokableFactory::class, 'foo']));
    }

    public function testWritesMapToConfigFileWhenSuccessful()
    {
        $command = $this->command;
        vfsStream::newFile('config/test.config.php')
            ->at($this->configDir)
            ->setContent(file_get_contents(__DIR__ . '/../TestAsset/config/test.config.php'));
        $config = vfsStream::url('project/config/test.config.php');

        $this->assertEquals(
            0,
            $command([$config, SimpleDependencyObject::class, InvokableFactory::class, 'controllers'])
        );

        $generated = include $config;
        $this->assertInternalType('array', $generated);
        $this->assertArrayHasKey('controllers', $generated);
        $this->assertInternalType('array', $generated['controllers']);
        $this->assertArrayHasKey('factories', $generated['controllers']);
        $this->assertInternalType('array', $generated['controllers']['factories']);
        $this->assertArrayHasKey(SimpleDependencyObject::class, $generated['controllers']['factories']);
        $this->assertEquals(
            InvokableFactory::class,
            $generated['controllers']['factories'][SimpleDependencyObject::class]
        );
    }

    public function testCanCreateConfigFileWhenSuccessful()
    {
        $command = $this->command;
        vfsStream::newDirectory('config', 0775)
            ->at($this->configDir);
        $config = vfsStream::url('project/config/test.config.php');

        $this->assertEquals(
            0,
            $command([$config, SimpleDependencyObject::class, InvokableFactory::class, 'controllers'])
        );

        $generated = include $config;
        $this->assertInternalType('array', $generated);
        $this->assertArrayHasKey('controllers', $generated);
        $this->assertInternalType('array', $generated['controllers']);
        $this->assertArrayHasKey('factories', $generated['controllers']);
        $this->assertInternalType('array', $generated['controllers']['factories']);
        $this->assertArrayHasKey(SimpleDependencyObject::class, $generated['controllers']['factories']);
        $this->assertEquals(
            InvokableFactory::class,
            $generated['controllers']['factories'][SimpleDependencyObject::class]
        );
    }
}
