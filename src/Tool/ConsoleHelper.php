<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Tool;

/**
 * Utilities for console tooling.
 *
 * Provides posix escape sequences for some oft-used colors, as well as for
 * resetting color.
 *
 * Provides a method for determining if a given console supports color output.
 */
class ConsoleHelper
{
    const COLOR_GREEN = "\033[32m";
    const COLOR_RED   = "\033[31m";
    const COLOR_RESET = "\033[0m";

    /**
     * @param resource $resource
     * @return bool
     */
    public static function supportsColorOutput($resource = STDOUT)
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            // Windows
            return false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM');
        }

        return function_exists('posix_isatty') && posix_isatty($resource);
    }
}
