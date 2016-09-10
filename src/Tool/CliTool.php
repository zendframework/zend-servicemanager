<?php
/**
 *  Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Tool;

use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Exception\InvalidArgumentException;

class CliTool
{
    /**
     * @param array $config
     * @param $className
     * @return array
     * @throws InvalidArgumentException
     */
    public static function createDependencyConfig(array $config, $className)
    {
        self::validateClassName($className);

        $reflectionClass = new \ReflectionClass($className);
        if (! $reflectionClass->getConstructor()) {
            return $config;
        }

        $constructorArguments = $reflectionClass->getConstructor()->getParameters();
        $constructorArguments = array_filter(
            $constructorArguments,
            function (\ReflectionParameter $argument) {
                return ! $argument->isOptional();
            }
        );

        // has no required parameters, we can just add an empty array
        if (empty($constructorArguments)) {
            $config[ConfigAbstractFactory::class][$className] = [];
            return $config;
        }

        foreach ($constructorArguments as $constructorArgument) {
            $argumentType = $constructorArgument->getClass();
            if (is_null($argumentType)) {
                throw new InvalidArgumentException(
                    'Cannot create config for ' . $className . ', it has no type hints in constructor'
                );
            }
            $argumentName = $argumentType->getName();
            $config = self::createDependencyConfig($config, $argumentName);
            $config[ConfigAbstractFactory::class][$className][] = $argumentName;
        }

        return $config;
    }

    /**
     * @param $className
     * @throws InvalidArgumentException
     */
    private static function validateClassName($className)
    {
        if (! is_string($className)) {
            throw new InvalidArgumentException('Class name must be a string, ' . gettype($className) . ' given');
        }

        if (! class_exists($className)) {
            throw new InvalidArgumentException('Cannot find class with name ' . $className);
        }
    }

    public static function createFactoryMappingsFromConfig(array $config)
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
            $config = self::createFactoryMappings($config, $className);
        }
        return $config;
    }

    public static function createFactoryMappings(array $config, $className)
    {
        self::validateClassName($className);

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
