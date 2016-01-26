<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Exception;

use PHPUnit_Framework_TestCase as TestCase;
use ProxyManager\Autoloader\AutoloaderInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use stdClass;
use Zend\ServiceManager\Exception\CyclicAliasException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\Proxy\LazyServiceFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ServiceManager\TestAsset\InvokableObject;

/**
 * @covers \Zend\ServiceManager\Exception\CyclicAliasException
 */
class CyclicAliasExceptionTest extends TestCase
{
    /**
     * @dataProvider aliasesProvider
     *
     * @param string[] $aliases
     * @param string   $expectedMessage
     *
     * @return void
     */
    public function testFromAliasesMap(array $aliases, $expectedMessage)
    {
        $exception = CyclicAliasException::fromAliasesMap($aliases);

        self::assertInstanceOf(CyclicAliasException::class, $exception);
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    /**
     * @return string[][]|string[][][]
     */
    public function aliasesProvider()
    {
        return [
            'empty set' => [
                [],
                'A cycle was detected within the following aliases map:

[

]'
            ],
            'acyclic set' => [
                [
                    'b' => 'a',
                    'd' => 'c',
                ],
                'A cycle was detected within the following aliases map:

[
"b" => "a"
"d" => "c"
]'
            ],
            'acyclic self-referencing set' => [
                [
                    'b' => 'a',
                    'c' => 'b',
                    'd' => 'c',
                ],
                'A cycle was detected within the following aliases map:

[
"b" => "a"
"c" => "b"
"d" => "c"
]'
            ],
            'cyclic set' => [
                [
                    'b' => 'a',
                    'a' => 'b',
                ],
                'A cycle was detected within the provided aliases:

[
"b" => "a" => "b"
]

The cycle was detected in the following alias map:

[
"b" => "a"
"a" => "b"
]'
            ],
            'cyclic set (indirect)' => [
                [
                    'b' => 'a',
                    'c' => 'b',
                    'a' => 'c',
                ],
                'A cycle was detected within the provided aliases:

[
"b" => "a" => "c" => "b"
]

The cycle was detected in the following alias map:

[
"b" => "a"
"c" => "b"
"a" => "c"
]'
            ],
            'cyclic set + acyclic set' => [
                [
                    'b' => 'a',
                    'a' => 'b',
                    'd' => 'c',
                ],
                'A cycle was detected within the provided aliases:

[
"b" => "a" => "b"
]

The cycle was detected in the following alias map:

[
"b" => "a"
"a" => "b"
"d" => "c"
]'
            ],
            'cyclic set + reference to cyclic set' => [
                [
                    'b' => 'a',
                    'a' => 'b',
                    'c' => 'a',
                ],
                'A cycle was detected within the provided aliases:

[
"b" => "a" => "b"
"c" => "a" => "b" => "c"
]

The cycle was detected in the following alias map:

[
"b" => "a"
"a" => "b"
"c" => "a"
]'
            ],
        ];
    }
}
