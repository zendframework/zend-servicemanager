<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Tool;

use Zend\ServiceManager\Exception;
use Zend\Stdlib\ConsoleHelper;

class ConfigDumperCommand
{
    const COMMAND_DUMP = 'dump';
    const COMMAND_ERROR = 'error';
    const COMMAND_HELP = 'help';

    const DEFAULT_SCRIPT_NAME = __CLASS__;

    const HELP_TEMPLATE = <<< EOH
<info>Usage:</info>

  %s [-h|--help|help] <configFile> <className>

<info>Arguments:</info>

  <info>-h|--help|help</info>    This usage message
  <info><configFile></info>      Path to an existing config file for which to generate
                    additional configuration. Must return an array.
  <info><className></info>       Name of the class to reflect and for which to generate
                    dependency configuration.

Generates to STDOUT a replacement configuration file containing dependency
configuration for the named class with which to configure the
ConfigAbstractFactory.
EOH;

    /**
     * @var ConsoleHelper
     */
    private $helper;

    /**
     * @var string
     */
    private $scriptName;

    /**
     * @param string $scriptName
     */
    public function __construct($scriptName = self::DEFAULT_SCRIPT_NAME, ConsoleHelper $helper = null)
    {
        $this->scriptName = $scriptName;
        $this->helper = $helper ?: new ConsoleHelper();
    }

    /**
     * @param array $args Argument list, minus script name
     * @return int Exit status
     */
    public function __invoke(array $args)
    {
        $arguments = $this->parseArgs($args);

        switch ($arguments->command) {
            case self::COMMAND_HELP:
                $this->help();
                return 0;
            case self::COMMAND_ERROR:
                $this->helper->writeErrorMessage($arguments->message);
                $this->help(STDERR);
                return 1;
            case self::COMMAND_DUMP:
                // fall-through
            default:
                break;
        }

        $dumper = new ConfigDumper();
        try {
            $config = $dumper->createDependencyConfig($arguments->config, $arguments->class);
        } catch (Exception\InvalidArgumentException $e) {
            $this->helper->writeErrorMessage(sprintf(
                'Unable to create config for "%s": %s',
                $arguments->class,
                $e->getMessage()
            ));
            $this->help(STDERR);
            return 1;
        }

        fwrite(STDOUT, $dumper->dumpConfigFile($config));
        return 0;
    }

    /**
     * @param array $args
     * @return \stdClass
     */
    private function parseArgs(array $args)
    {
        if (! count($args)) {
            return $this->createArguments(self::COMMAND_HELP);
        }

        $arg1 = array_shift($args);

        if (in_array($arg1, ['-h', '--help', 'help'], true)) {
            return $this->createArguments(self::COMMAND_HELP);
        }

        if (! count($args)) {
            return $this->createArguments(self::COMMAND_ERROR, null, null, 'Missing class name');
        }

        if (! file_exists($arg1)) {
            return $this->createArguments(self::COMMAND_ERROR, null, null, sprintf(
                'Cannot find configuration file at path "%s"',
                $arg1
            ));
        }

        $config = require $arg1;

        if (! is_array($config)) {
            return $this->createArguments(self::COMMAND_ERROR, null, null, sprintf(
                'Configuration at path "%s" does not return an array.',
                $arg1
            ));
        }

        $class = array_shift($args);

        if (! class_exists($class)) {
            return $this->createArguments(self::COMMAND_ERROR, null, null, sprintf(
                'Class "%s" does not exist or could not be autoloaded.',
                $class
            ));
        }

        return $this->createArguments(self::COMMAND_DUMP, $config, $class);
    }

    /**
     * @param resource $resource Defaults to STDOUT
     * @return void
     */
    private function help($resource = STDOUT)
    {
        $this->helper->writeLine(sprintf(
            self::HELP_TEMPLATE,
            $this->scriptName
        ), true, $resource);
    }

    /**
     * @param string $command
     * @param array|null $config Parsed configuration.
     * @param string|null $class Name of class to reflect.
     * @param string|null $message Error message, if any.
     * @return \stdClass
     */
    private function createArguments($command, $config = null, $class = null, $error = null)
    {
        return (object) [
            'command' => $command,
            'config'  => $config,
            'class'   => $class,
            'message' => $error,
        ];
    }
}
