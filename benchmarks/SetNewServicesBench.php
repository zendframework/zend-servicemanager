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
use ZendBench\ServiceManager\BenchAsset\DelegatorFactoryFoo;

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
        ];

        for ($i = 0; $i <= self::NUM_SERVICES; $i++) {
            $config['factories']["factory_$i"] = BenchAsset\FactoryFoo::class;
            $config['aliases']["alias_$i"]     = "service_$i";
            $config['abstract_factories'][] = BenchAsset\AbstractFactoryFoo::class;
            $config['invokables']["invokable_$i"] = BenchAsset\Foo::class;
            $config['delegators']["delegator_$i"] = [ DelegatorFactoryFoo::class ];
        }

        $this->initializer = new BenchAsset\InitializerFoo();
        $this->abstractFactory = new BenchAsset\AbstractFactoryFoo();
        $this->sm = new ServiceManager($config);
    }


    public function benchSetService()
    {
        $sm = clone $this->sm;
        $sm->setService('service2', new \stdClass());
    }

    /**
     * @todo @link https://github.com/phpbench/phpbench/issues/304
     */
    public function benchSetFactory()
    {
        $sm = clone $this->sm;
        $sm->setFactory('factory2', BenchAsset\FactoryFoo::class);
    }

    /**
     * @todo @link https://github.com/phpbench/phpbench/issues/304
     */
    public function benchSetAlias()
    {
        $sm = clone $this->sm;
        $sm->setAlias('factoryAlias2', 'factory1');
    }

    /**
     * @todo @link https://github.com/phpbench/phpbench/issues/304
     */
    public function benchOverrideAlias()
    {
        $sm = clone $this->sm;
        $sm->setAlias('recursiveFactoryAlias1', 'factory1');
    }

    /**
     * @todo @link https://github.com/phpbench/phpbench/issues/304
     */
    public function benchSetInvokableClass()
    {
        $sm = clone $this->sm;
        $sm->setInvokableClass(BenchAsset\Foo::class, BenchAsset\Foo::class);
    }

    /**
     * @todo @link https://github.com/phpbench/phpbench/issues/304
     */
    public function benchAddDelegator()
    {
        $sm = clone $this->sm;
        $sm->addDelegator(BenchAsset\Foo::class, DelegatorFactoryFoo::class);
    }

    /**
     * @todo @link https://github.com/phpbench/phpbench/issues/304
     */
    public function benchAddInitializerByClassName()
    {
        $sm = clone $this->sm;
        $sm->addInitializer(BenchAsset\InitializerFoo::class);
    }

    /**
     * @todo @link https://github.com/phpbench/phpbench/issues/304
     */
    public function benchAddInitializerByInstance()
    {
        $sm = clone $this->sm;
        $sm->addInitializer($this->initializer);
    }

    /**
     * @todo @link https://github.com/phpbench/phpbench/issues/304
     */
    public function benchAddAbstractFactoryByClassName()
    {
        $sm = clone $this->sm;
        $sm->addAbstractFactory(BenchAsset\AbstractFactoryFoo::class);
    }

    /**
     * @todo @link https://github.com/phpbench/phpbench/issues/304
     */
    public function benchAddAbstractFactoryByInstance()
    {
        $sm = clone $this->sm;
        $sm->addAbstractFactory($this->abstractFactory);
    }
}
