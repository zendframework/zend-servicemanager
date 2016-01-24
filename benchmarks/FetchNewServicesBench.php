<?php

namespace ZendBench\ServiceManager;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use Zend\ServiceManager\ServiceManager;

class FetchNewServicesBench
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
        ]);
    }

    /**
     * @Revs(1000)
     * @Iterations(10)
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
     * @Iterations(10)
     * @Warmup(2)
     */
    public function benchBuildFactory1()
    {
        // @todo workaround until phpbench provides initialization around each loop, excluded from measurement
        $sm = clone $this->sm;

        $sm->build('factory1');
    }

    /**
     * @Revs(1000)
     * @Iterations(10)
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
     * @Iterations(10)
     * @Warmup(2)
     */
    public function benchBuildInvokable1()
    {
        // @todo workaround until phpbench provides initialization around each loop, excluded from measurement
        $sm = clone $this->sm;

        $sm->build('invokable1');
    }
}
