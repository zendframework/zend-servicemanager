<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Exception;

use function sprintf;

class CyclicAliasException extends InvalidArgumentException
{
    /**
     * @param string   conflicting alias key
     * @param string[] $aliases map of referenced services, indexed by alias name (string)
     *
     * @return self
     */
    public static function fromCyclicAlias($alias, $aliases)
    {
        $msg = $alias;
        $cursor = $alias;
        while($aliases[$cursor] !== $alias) {
            $cursor = $aliases[$cursor];
            $msg .= ' -> '. $cursor;
        }
        $msg .= ' -> ' . $alias . PHP_EOL;
        return new self(sprintf(
            "A cycle was detected within the aliases defintions:\n%s",
            $msg
            ));
    }
}
