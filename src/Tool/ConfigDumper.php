<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Tool;

use Interop\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;
use Traversable;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Exception\InvalidArgumentException;

use function array_filter;
use function array_key_exists;
use function class_exists;
use function date;
use function get_class;
use function gettype;
use function implode;
use function interface_exists;
use function is_array;
use function is_int;
use function is_null;
use function is_string;
use function sprintf;
use function str_repeat;
use function var_export;

class ConfigDumper
{
    use ConfigDumperTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param array $config
     * @param string $className
     * @param bool $ignoreUnresolved
     * @return array
     * @throws InvalidArgumentException for invalid $className
     */
    public function createDependencyConfig(array $config, $className, $ignoreUnresolved = false)
    {
        $this->validateClassName($className);

        $reflectionClass = new ReflectionClass($className);

        // class is an interface; do nothing
        if ($reflectionClass->isInterface()) {
            return $config;
        }

        // class has no constructor, treat it as an invokable
        if (! $reflectionClass->getConstructor()) {
            return $this->createInvokable($config, $className);
        }

        $constructorArguments = $reflectionClass->getConstructor()->getParameters();
        $constructorArguments = array_filter(
            $constructorArguments,
            function (ReflectionParameter $argument) {
                return ! $argument->isOptional();
            }
        );

        // has no required parameters, treat it as an invokable
        if (empty($constructorArguments)) {
            return $this->createInvokable($config, $className);
        }

        $classConfig = [];

        foreach ($constructorArguments as $constructorArgument) {
            $argumentType = $constructorArgument->getClass();
            if (is_null($argumentType)) {
                if ($ignoreUnresolved) {
                    // don't throw an exception, just return the previous config
                    return $config;
                }
                // don't throw an exception if the class is an already defined service
                if ($this->container && $this->container->has($className)) {
                    return $config;
                }
                throw new InvalidArgumentException(sprintf(
                    'Cannot create config for constructor argument "%s", '
                    . 'it has no type hint, or non-class/interface type hint',
                    $constructorArgument->getName()
                ));
            }
            $argumentName = $argumentType->getName();
            $config = $this->createDependencyConfig($config, $argumentName, $ignoreUnresolved);
            $classConfig[] = $argumentName;
        }

        $config[ConfigAbstractFactory::class][$className] = $classConfig;

        return $config;
    }

    /**
     * @param $className
     * @throws InvalidArgumentException if class name is not a string or does
     *     not exist.
     */
    private function validateClassName($className)
    {
        if (! is_string($className)) {
            throw new InvalidArgumentException('Class name must be a string, ' . gettype($className) . ' given');
        }

        if (! class_exists($className) && ! interface_exists($className)) {
            throw new InvalidArgumentException('Cannot find class or interface with name ' . $className);
        }
    }

    /**
     * @param array $config
     * @param string $className
     * @return array
     */
    private function createInvokable(array $config, $className)
    {
        $config[ConfigAbstractFactory::class][$className] = [];
        return $config;
    }

    /**
     * @param array $config
     * @return array
     * @throws InvalidArgumentException if ConfigAbstractFactory configuration
     *     value is not an array.
     */
    public function createFactoryMappingsFromConfig(array $config)
    {
        if (! array_key_exists(ConfigAbstractFactory::class, $config)) {
            return $config;
        }

        if (! is_array($config[ConfigAbstractFactory::class])) {
            throw new InvalidArgumentException(
                'Config key for ' . ConfigAbstractFactory::class . ' should be an array, ' . gettype(
                    $config[ConfigAbstractFactory::class]
                ) . ' given'
            );
        }

        foreach ($config[ConfigAbstractFactory::class] as $className => $dependency) {
            $config = $this->createFactoryMappings($config, $className);
        }
        return $config;
    }

    /**
     * @param array $config
     * @param string $className
     * @return array
     */
    public function createFactoryMappings(array $config, $className)
    {
        $this->validateClassName($className);

        if (array_key_exists('service_manager', $config)
            && array_key_exists('factories', $config['service_manager'])
            && array_key_exists($className, $config['service_manager']['factories'])
        ) {
            return $config;
        }

        $config['service_manager']['factories'][$className] = ConfigAbstractFactory::class;
        return $config;
    }
}
