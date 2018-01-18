<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Tool;

use ReflectionClass;
use ReflectionParameter;
use Zend\ServiceManager\Exception\InvalidArgumentException;

use function array_filter;
use function array_map;
use function array_shift;
use function count;
use function implode;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strrpos;
use function substr;

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
            str_replace('\\' . $class, '', $className),
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
    private function getClassName($className)
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
                if ($argument->isOptional()) {
                    return false;
                }

                if (null === $argument->getClass()) {
                    throw new InvalidArgumentException(sprintf(
                        'Cannot identify type for constructor argument "%s"; '
                        . 'no type hint, or non-class/interface type hint',
                        $argument->getName()
                    ));
                }

                return true;
            }
        );

        if (empty($constructorParameters)) {
            return [];
        }

        return array_map(function (ReflectionParameter $parameter) {
            return $parameter->getClass()->getName();
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
