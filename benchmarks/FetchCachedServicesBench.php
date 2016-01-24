<?php

namespace ZendBench\ServiceManager;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use Zend\ServiceManager\ServiceManager;

class FetchCachedServicesBench
{
    /**
     * @var ServiceManager
     */
    private $sm;

    public function __construct()
    {
        $this->sm = new ServiceManager([
            'factories' => [
                'factory1' => BenchAsset\FactoryFoo::class,
            ],
            'invokables' => [
                'invokable1' => BenchAsset\Foo::class,
            ],
            'services' => [
                'service1' => new \stdClass(),
            ],
            'aliases' => [
                'alias1'          => 'service1',
                'recursiveAlias1' => 'alias1',
                'recursiveAlias2' => 'recursiveAlias1',
            ],
            'abstract_factories' => [
                BenchAsset\AbstractFactoryFoo::class
            ]
        ]);

        // forcing initialization of all the services
        $this->sm->get('factory1');
        $this->sm->get('invokable1');
        $this->sm->get('service1');
        $this->sm->get('alias1');
        $this->sm->get('recursiveAlias1');
        $this->sm->get('recursiveAlias2');
    }

    /**
     * Fetch the factory services
     *
     * @Revs(1000)
     * @Iterations(20)
     * @Warmup(2)
     */
    public function benchFetchFactory1()
    {
        // @todo workaround until phpbench provides initialization around each loop, excluded from measurement
        $sm = clone $this->sm;

        $sm->get('factory1');
    }

    /**
     * @Revs(1000)
     * @Iterations(20)
     * @Warmup(2)
     */
    public function benchFetchInvokable1()
    {
        // @todo workaround until phpbench provides initialization around each loop, excluded from measurement
        $sm = clone $this->sm;

        $sm->get('invokable1');
    }

    /**
     * @Revs(1000)
     * @Iterations(20)
     * @Warmup(2)
     */
    public function benchFetchService1()
    {
        // @todo workaround until phpbench provides initialization around each loop, excluded from measurement
        $sm = clone $this->sm;

        $sm->get('service1');
    }

    /**
     * @Revs(1000)
     * @Iterations(20)
     * @Warmup(2)
     */
    public function benchFetchAlias1()
    {
        // @todo workaround until phpbench provides initialization around each loop, excluded from measurement
        $sm = clone $this->sm;

        $sm->get('alias1');
    }

    /**
     * @Revs(1000)
     * @Iterations(20)
     * @Warmup(2)
     */
    public function benchFetchRecursiveAlias1()
    {
        // @todo workaround until phpbench provides initialization around each loop, excluded from measurement
        $sm = clone $this->sm;

        $sm->get('recursiveAlias1');
    }

    /**
     * @Revs(1000)
     * @Iterations(20)
     * @Warmup(2)
     */
    public function benchFetchRecursiveAlias2()
    {
        // @todo workaround until phpbench provides initialization around each loop, excluded from measurement
        $sm = clone $this->sm;

        $sm->get('recursiveAlias2');
    }

    /**
     * @Revs(1000)
     * @Iterations(20)
     * @Warmup(2)
     */
    public function benchFetchAbstractFactoryService()
    {
        // @todo workaround until phpbench provides initialization around each loop, excluded from measurement
        $sm = clone $this->sm;

        $sm->get('foo');
    }
}
