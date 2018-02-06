<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcBench\ServiceManager;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use Mxc\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Mxc\ServiceManager\ServiceManager;

/**
 * @Revs(1000)
 * @Iterations(10)
 * @Warmup(2)
 */
class FetchNewServiceUsingConfigAbstractFactoryAsFactoryBench
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
            'factories' => [
                BenchAsset\Dependency::class => ConfigAbstractFactory::class,
                BenchAsset\ServiceWithDependency::class => ConfigAbstractFactory::class,
                BenchAsset\ServiceDependingOnConfig::class => ConfigAbstractFactory::class,
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
