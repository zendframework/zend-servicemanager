<?php
/**
 *  Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Tool;

use ReflectionClass;
use ReflectionParameter;

class FactoryCreator
{
    const FACTORY_TEMPLATE = <<<'EOT'
<?php

namespace %s;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use %s;

class %sFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return %s
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new %s(%s);
    }
}

EOT;

    /**
     * @param string $className
     * @return string
     */
    public function createFactory($className)
    {
        $class = $this->getClassName($className);

        return sprintf(
            self::FACTORY_TEMPLATE,
            str_replace($class, '', $className) . 'Factory',
            $className,
            $class,
            $class,
            $class,
            $this->createArgumentString($className)
        );
    }

    /**
     * @param $className
     * @return string
     */
    private function getClassName($className):string
    {
        $class = substr($className, strrpos($className, '\\') + 1);
        return $class;
    }

    /**
     * @param string $className
     * @return array
     */
    private function getConstructorParameters($className)
    {
        $reflectionClass = new ReflectionClass($className);

        if (! $reflectionClass || ! $reflectionClass->getConstructor()) {
            return [];
        }

        $constructorParameters = $reflectionClass->getConstructor()->getParameters();

        if (empty($constructorParameters)) {
            return [];
        }

        $constructorParameters = array_filter(
            $constructorParameters,
            function (ReflectionParameter $argument) {
                return ! $argument->isOptional();
            }
        );

        if (empty($constructorParameters)) {
            return [];
        }

        return array_map(function ($parameter) {
            return $parameter->getType();
        }, $constructorParameters);
    }

    /**
     * @param string $className
     * @return string
     */
    private function createArgumentString($className)
    {
        $arguments = array_map(function ($dependency) {
            return sprintf('$container->get(\\%s::class)', $dependency);
        }, $this->getConstructorParameters($className));

        switch (count($arguments)) {
            case 0:
                return '';
            case 1:
                return array_shift($arguments);
            default:
                $argumentPad = str_repeat(' ', 12);
                $closePad = str_repeat(' ', 8);
                return sprintf(
                    "\n%s%s\n%s",
                    $argumentPad,
                    implode(",\n" . $argumentPad, $arguments),
                    $closePad
                );
        }
    }
}
