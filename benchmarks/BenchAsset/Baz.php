<?php
namespace ZendBench\ServiceManager\BenchAsset;

class Baz
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }
}
