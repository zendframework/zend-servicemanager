<?php
/**
 * @link      https://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\TestAsset;

use stdClass;
use Zend\ServiceManager\ServiceManager;

class PreconfiguredServiceManager extends ServiceManager
{
    protected $services = [];

    protected $aliases = [
        'alias1' => 'alias2',
        'alias2' => 'service',
    ];

    protected $factories = [
        'delegator' => SampleFactory::class,
        'factory'   => SampleFactory::class,
    ];

    protected $delegators = [
        'delegator' => [
            PassthroughDelegatorFactory::class,
        ],
    ];

    protected $invokables = [
        'invokable' => stdClass::class,
    ];

    protected $initializers = [
        TaggingInitializer::class,
    ];

    protected $abstractFactories = [
        AbstractFactoryFoo::class,
    ];

    public function __construct(array $config = [])
    {
        $this->services = [
            'service' => new stdClass(),
        ];
        parent::__construct($config);
    }
}
