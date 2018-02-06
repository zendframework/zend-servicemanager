<?php
/**
 * @link      https://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager\TestAsset;

use stdClass;
use Mxc\ServiceManager\ServiceManager;

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
