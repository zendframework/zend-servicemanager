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
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\ServiceManager;

/**
 * @Revs(1000)
 * @Iterations(10)
 * @Warmup(2)
 */
class FetchNewServiceViaConfigAbstractFactoryBench
{
    /**
     * @var ServiceManager
     */
    private $sm;

    public function __construct()
    {
        $this->sm = new ServiceManager([
            'services' => [
                'config' => [
                    ConfigAbstractFactory::class => [
                        BenchAsset\Dependency::class => [],
                        BenchAsset\ServiceWithDependency::class => [
                            BenchAsset\Dependency::class,
                        ],
                        BenchAsset\ServiceDependingOnConfig::class => [
                            'config',
                        ],
                    ],
                ],
            ],
            'abstract_factories' => [
                ConfigAbstractFactory::class,
            ],
        ]);
    }

    public function benchFetchServiceWithNoDependencies()
    {
        $sm = clone $this->sm;

        $sm->get(BenchAsset\Dependency::class);
    }

    public function benchBuildServiceWithNoDependencies()
    {
        $sm = clone $this->sm;

        $sm->build(BenchAsset\Dependency::class);
    }

    public function benchFetchServiceDependingOnConfig()
    {
        $sm = clone $this->sm;

        $sm->get(BenchAsset\ServiceDependingOnConfig::class);
    }

    public function benchBuildServiceDependingOnConfig()
    {
        $sm = clone $this->sm;

        $sm->build(BenchAsset\ServiceDependingOnConfig::class);
    }

    public function benchFetchServiceWithDependency()
    {
        $sm = clone $this->sm;

        $sm->get(BenchAsset\ServiceWithDependency::class);
    }

    public function benchBuildServiceWithDependency()
    {
        $sm = clone $this->sm;

        $sm->build(BenchAsset\ServiceWithDependency::class);
    }
}
