<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Tool;

use Zend\ServiceManager\Exception;
use Zend\Stdlib\ConsoleHelper;

class FactoryMapperCommand
{
    use ConfigDumperTrait;

    const COMMAND_MAP = 'map';
    const COMMAND_ERROR = 'error';
    const COMMAND_HELP = 'help';

    const DEFAULT_CONFIG_KEY = 'service_manager';
    const DEFAULT_SCRIPT_NAME = __CLASS__;

    const HELP_TEMPLATE = <<< EOH
<info>Usage:</info>

  %s [-h|--help|help] <configFile> <className> <factoryName> [<key>]

<info>Arguments:</info>

  <info>-h|--help|help</info>    This usage message
  <info><configFile></info>      Path to an config file in which to map the factory.
                    If the file does not exist, it will be created. If
                    it does exist, it must return an array.
  <info><className></info>       Name of the class to map to a factory.
  <info><factoryName></info>     Name of the factory class to use with <info><className></info>.
  <info>[<key>]</info>           (Optional) The top-level configuration key under which
                    the factory map should appear; defaults to
                    "service_manager".

Reads the provided configuration file, creating it if necessary, and
injects it with a mapping of the given class to its factory. If <info>key</info> is
provided, the factory configuration will be injected under that key, and
not the default "service_manager" key.
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
            case self::COMMAND_MAP:
                // fall-through
            default:
                break;
        }

        $dumper = new ConfigDumper();
        try {
            $config = $this->createFactoryMap(
                $arguments->config,
                $arguments->class,
                $arguments->factory,
                $arguments->key
            );
        } catch (Exception\InvalidArgumentException $e) {
            $this->helper->writeErrorMessage(sprintf(
                'Unable to create factory map for "%s": %s',
                $arguments->class,
                $e->getMessage()
            ));
            $this->help(STDERR);
            return 1;
        }

        file_put_contents($arguments->configFile, $this->dumpConfigFile($config));

        $this->helper->writeLine(sprintf(
            '<info>[DONE]</info> Changes written to %s',
            $arguments->configFile
        ));
        return 0;
    }

    /**
     * @param array $args
     * @return \stdClass
     */
    private function parseArgs(array $args)
    {
        if (! count($args)) {
            return $this->createHelpArgument();
        }

        $arg1 = array_shift($args);

        if (in_array($arg1, ['-h', '--help', 'help'], true)) {
            return $this->createHelpArgument();
        }

        if (count($args) < 2) {
            return $this->createErrorArgument('Missing arguments');
        }

        $configFile = $arg1;

        switch (file_exists($configFile)) {
            case true:
                $config = require $configFile;
                if (! is_array($config)) {
                    return $this->createErrorArgument(sprintf(
                        'Configuration at path "%s" does not return an array.',
                        $configFile
                    ));
                }
                break;
            case false:
                // fall-through
            default:
                if (! is_writable(dirname($configFile))) {
                    return $this->createErrorArgument(sprintf(
                        'Configuration at path "%s" cannot be created; directory is not writable.',
                        $configFile
                    ));
                }

                $config = [];
                break;
        }

        $class = array_shift($args);

        if (! class_exists($class)) {
            return $this->createErrorArgument(sprintf(
                'Class "%s" does not exist or could not be autoloaded.',
                $class
            ));
        }

        $factory = array_shift($args);

        if (! class_exists($factory)) {
            return $this->createErrorArgument(sprintf(
                'Factory "%s" does not exist or could not be autoloaded.',
                $factory
            ));
        }

        $key = count($args) ? array_shift($args) : self::DEFAULT_CONFIG_KEY;

        if (array_key_exists($key, $config)
            && ! is_array($config[$key])
        ) {
            return $this->createErrorArgument(sprintf(
                'Config file "%s" contains the key "%s", but it is not an array; aborting.',
                $configFile,
                $key
            ));
        }

        return $this->createArguments(self::COMMAND_MAP, $configFile, $config, $class, $factory, $key);
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
     * @param string $configFile File from which config originates, and to
     *     which it will be written.
     * @param array $config Parsed configuration.
     * @param string $class Name of class to map.
     * @param string $factory Name of factory to which to map.
     * @param string $key Name of config key under which to create mapping.
     * @return \stdClass
     */
    private function createArguments($command, $configFile, $config, $class, $factory, $key)
    {
        return (object) [
            'command'    => $command,
            'configFile' => $configFile,
            'config'     => $config,
            'class'      => $class,
            'factory'    => $factory,
            'key'        => $key,
        ];
    }

    /**
     * @param string $message
     * @return \stdClass
     */
    private function createErrorArgument($message)
    {
        return (object) [
            'command' => self::COMMAND_ERROR,
            'message' => $message,
        ];
    }

    /**
     * @return \stdClass
     */
    private function createHelpArgument()
    {
        return (object) [
            'command' => self::COMMAND_HELP,
        ];
    }

    /**
     * @param array $config Configuration to inject.
     * @param string $class Class name to map to factory.
     * @param string $factory Factory to which to map.
     * @param string $key Top-level configuration key under which to create map.
     * @return array
     * @throws Exception\InvalidArgumentException when $config[$key]['factories']
     *     is not an array value.
     */
    private function createFactoryMap(array $config, $class, $factory, $key)
    {
        if (! array_key_exists($key, $config)) {
            $config[$key] = [];
        }

        if (! array_key_exists('factories', $config[$key])) {
            $config[$key]['factories'] = [];
        }

        if (! is_array($config[$key]['factories'])) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Configuration at key "%s" contains factories configuration, but it is not an array.',
                $key
            ));
        }

        $config[$key]['factories'][$class] = $factory;
        return $config;
    }
}
