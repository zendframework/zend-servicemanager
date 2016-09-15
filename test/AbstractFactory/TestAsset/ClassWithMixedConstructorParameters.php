<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\AbstractFactory\TestAsset;

class ClassWithMixedConstructorParameters
{
    public $config;
    public $options;
    public $sample;
    public $validators;

    public function __construct(
        SampleInterface $sample,
        ValidatorPluginManager $validators,
        array $config,
        array $options = null
    ) {
        $this->sample = $sample;
        $this->validators = $validators;
        $this->config = $config;
        $this->options = $options;
    }
}
