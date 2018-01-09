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
     * @dataProvider aliasesProvider
     *
     * @param string   conflicting alias key
     * @param string[] $aliases
     * @param string   $expectedMessage
     *
     * @return void
     */
    public function testFromCyclicAlias($alias, array $aliases, $expectedMessage)
    {
        $exception = CyclicAliasException::fromCyclicAlias($alias, $aliases);

        self::assertInstanceOf(CyclicAliasException::class, $exception);
        self::assertSame($expectedMessage, $exception->getCycle());
    }

    /**
     * Test data provider for testFromCyclicAlias
     *
     * @return string[][]|string[][][]
     */
    public function aliasesProvider()
    {
        return
        [
            [
                'a',
                [
                    'a' => 'a',
                ],
                "a -> a\n",
            ],
            [
                'a',
                [
                    'a' => 'b',
                    'b' => 'a'
                ],
                "a -> b -> a\n",
            ],
            [
                'b',
                [
                    'a' => 'b',
                    'b' => 'a'
                ],
                "b -> a -> b\n",
            ],
            [
                'b',
                [
                    'a' => 'b',
                    'b' => 'a',
                ],
                "b -> a -> b\n",
            ],
            [
                'a',
                [
                    'a' => 'b',
                    'b' => 'c',
                    'c' => 'a',
                ],
                "a -> b -> c -> a\n",
            ],
            [
                'b',
                [
                    'a' => 'b',
                    'b' => 'c',
                    'c' => 'a',
                ],
                "b -> c -> a -> b\n",
            ],
            [
                'c',
                [
                    'a' => 'b',
                    'b' => 'c',
                    'c' => 'a',
                ],
                "c -> a -> b -> c\n",
            ],
        ];
    }
}
