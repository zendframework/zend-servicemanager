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

    /**
     * @param string $className
     * @return string
     */
    public function createFactory($className)
    {
        $class = $this->getClassName($className);
        $namespace = str_replace($class, '', $className) . 'Factory';
        $constructorParameters = $this->getConstructorParameters($className);

        $factory = '<?php' . PHP_EOL;
        $factory .= PHP_EOL;
        $factory .= 'namespace ' . $namespace . ';' . PHP_EOL;
        $factory .= PHP_EOL;

        $factory .= 'use Interop\Container\ContainerInterface;' . PHP_EOL;
        $factory .= 'use Zend\ServiceManager\Factory\FactoryInterface;' . PHP_EOL;
        $factory .= 'use ' . $className . ';' . PHP_EOL;
        $factory .= PHP_EOL;

        $factory .= 'class ' . $class . 'Factory implements FactoryInterface' . PHP_EOL;
        $factory .= '{' . PHP_EOL;
        $factory .= '    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)' . PHP_EOL;
        $factory .= '    {' . PHP_EOL;

        foreach ($constructorParameters as $variable => $fqns) {
            $factory .= '        ' . $variable . ' = $container->get(\\' . $fqns . '::class);' . PHP_EOL;
        }
        if (! empty($constructorParameters)) {
            $factory .= PHP_EOL;
        }

        $factory .= '        return new ' . $class . '(' . implode(', ', array_keys($constructorParameters)) . ');' . PHP_EOL;
        $factory .= '    }' . PHP_EOL;
        $factory .= '}' . PHP_EOL;
        $factory .= PHP_EOL;

        return $factory;
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
        $constructorParameters = array_filter(
            $constructorParameters,
            function (ReflectionParameter $argument) {
                return ! $argument->isOptional();
            }
        );

        $values = [];
        foreach ($constructorParameters as $parameter) {
            $values['$' . lcfirst($parameter->getName())] = $parameter->getType();
        }

        return $values;
    }
}
