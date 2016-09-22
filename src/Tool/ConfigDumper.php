<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Tool;

use ReflectionClass;
use ReflectionParameter;
use Traversable;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Exception\InvalidArgumentException;

class ConfigDumper
{
    use ConfigDumperTrait;

    /**
     * @param array $config
     * @param string $className
     * @return array
     * @throws InvalidArgumentException for invalid $className
     */
    public function createDependencyConfig(array $config, $className)
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

        $config[ConfigAbstractFactory::class][$className] = [];

        foreach ($constructorArguments as $constructorArgument) {
            $argumentType = $constructorArgument->getClass();
            if (is_null($argumentType)) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot create config for constructor argument "%s", '
                    . 'it has no type hint, or non-class/interface type hint',
                    $constructorArgument->getName()
                ));
            }
            $argumentName = $argumentType->getName();
            $config = $this->createDependencyConfig($config, $argumentName);
            $config[ConfigAbstractFactory::class][$className][] = $argumentName;
        }

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
