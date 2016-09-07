<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

require_once(__DIR__ . '/../vendor/autoload.php');
chdir(__DIR__ . '/../');

$configPath = isset($argv[1]) ? $argv[1] : '';
$className = isset($argv[2]) ? $argv[2] : '';

// Retrieve configuration
if (!file_exists($configPath)) {
    throw new InvalidArgumentException('Cannot find any config at ' . $configPath);
}

$appConfig = require $configPath;

\Zend\ServiceManager\Tool\CliTool::createDependencyConfig($appConfig, $className);
