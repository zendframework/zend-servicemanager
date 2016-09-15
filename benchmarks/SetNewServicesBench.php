<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendBench\ServiceManager;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use Zend\ServiceManager\ServiceManager;

/**
 * @Revs(1000)
 * @Iterations(10)
 * @Warmup(2)
 */
class SetNewServicesBench
{
    const NUM_SERVICES = 100;

    /**
     * @var ServiceManager
     */
    private $sm;

    public function __construct()
    {
        $config = [
            'factories'          => [
                'factory1' => BenchAsset\FactoryFoo::class,
            ],
            'invokables'         => [
                'invokable1' => BenchAsset\Foo::class,
            ],
            'services'           => [
                'service1' => new \stdClass(),
            ],
            'aliases'            => [
                'factoryAlias1'          => 'factory1',
                'recursiveFactoryAlias1' => 'factoryAlias1',
                'recursiveFactoryAlias2' => 'recursiveFactoryAlias1',
            ],
            'abstract_factories' => [
                BenchAsset\AbstractFactoryFoo::class
            ],
        ];

        for ($i = 0; $i <= self::NUM_SERVICES; $i++) {
            $config['factories']["factory_$i"] = BenchAsset\FactoryFoo::class;
            $config['aliases']["alias_$i"]     = "service_$i";
        }

        $this->sm = new ServiceManager($config);
    }

    public function benchSetFactory()
    {
        // @todo @link https://github.com/phpbench/phpbench/issues/304
        $sm = clone $this->sm;

        $sm->setFactory('factory2', BenchAsset\FactoryFoo::class);
    }

    public function benchSetAlias()
    {
        // @todo @link https://github.com/phpbench/phpbench/issues/304
        $sm = clone $this->sm;

        $sm->setAlias('factoryAlias2', 'factory1');
    }

    public function benchSetAliasOverrided()
    {
        // @todo @link https://github.com/phpbench/phpbench/issues/304
        $sm = clone $this->sm;

        $sm->setAlias('recursiveFactoryAlias1', 'factory1');
    }
}
