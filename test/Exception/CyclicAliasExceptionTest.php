<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Exception;

use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\Exception\CyclicAliasException;

/**
 * @covers \Zend\ServiceManager\Exception\CyclicAliasException
 */
class CyclicAliasExceptionTest extends TestCase
{

    /**
     * @dataProvider cyclicAliasProvider
     *
     * @param string   $alias, conflicting alias key
     * @param string[] $aliases
     * @param string   $expectedMessage
     */
    public function testFromCyclicAlias($alias, array $aliases, $expectedMessage)
    {
        $exception = CyclicAliasException::fromCyclicAlias($alias, $aliases);

        self::assertInstanceOf(CyclicAliasException::class, $exception);
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    /**
     * Test data provider for testFromCyclicAlias
     *
     * @return string[][]|string[][][]
     */
    public function cyclicAliasProvider()
    {
        return [
            [
                'a',
                [
                    'a' => 'a',
                ],
                "A cycle was detected within the aliases definitions:\n"
                . "a -> a\n",
            ],
            [
                'a',
                [
                    'a' => 'b',
                    'b' => 'a'
                ],
                "A cycle was detected within the aliases definitions:\n"
                . "a -> b -> a\n",
            ],
            [
                'b',
                [
                    'a' => 'b',
                    'b' => 'a'
                ],
                "A cycle was detected within the aliases definitions:\n"
                . "b -> a -> b\n",
            ],
            [
                'b',
                [
                    'a' => 'b',
                    'b' => 'a',
                ],
                "A cycle was detected within the aliases definitions:\n"
                . "b -> a -> b\n",
            ],
            [
                'a',
                [
                    'a' => 'b',
                    'b' => 'c',
                    'c' => 'a',
                ],
                "A cycle was detected within the aliases definitions:\n"
                . "a -> b -> c -> a\n",
            ],
            [
                'b',
                [
                    'a' => 'b',
                    'b' => 'c',
                    'c' => 'a',
                ],
                "A cycle was detected within the aliases definitions:\n"
                . "b -> c -> a -> b\n",
            ],
            [
                'c',
                [
                    'a' => 'b',
                    'b' => 'c',
                    'c' => 'a',
                ],
                "A cycle was detected within the aliases definitions:\n"
                . "c -> a -> b -> c\n",
            ],
        ];
    }

    /**
     * @dataProvider aliasesProvider
     *
     * @param string[] $aliases
     * @param string   $expectedMessage
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
                'Cycles were detected within the provided aliases:

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
                'Cycles were detected within the provided aliases:

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
                'Cycles were detected within the provided aliases:

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
                'Cycles were detected within the provided aliases:

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
