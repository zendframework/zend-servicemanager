<?php
namespace ZendBench\ServiceManager\BenchAsset;

class Bar
{
    protected $options;

    public function __construct($options = null)
    {
        $this->options = $options;
    }
}
