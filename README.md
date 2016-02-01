# zend-servicemanager

Master:
[![Build Status](https://travis-ci.org/zendframework/zend-servicemanager.svg?branch=master)](https://travis-ci.org/zendframework/zend-servicemanager)
[![Coverage Status](https://coveralls.io/repos/zendframework/zend-servicemanager/badge.svg?branch=master)](https://coveralls.io/r/zendframework/zend-servicemanager?branch=master)
Develop:
[![Build Status](https://travis-ci.org/zendframework/zend-servicemanager.svg?branch=develop)](https://travis-ci.org/zendframework/zend-servicemanager)
[![Coverage Status](https://coveralls.io/repos/zendframework/zend-servicemanager/badge.svg?branch=develop)](https://coveralls.io/r/zendframework/zend-servicemanager?branch=develop)

The Service Locator design pattern is implemented by the `Zend\ServiceManager`
component. The Service Locator is a service/object locator, tasked with
retrieving other objects.

- File issues at https://github.com/zendframework/zend-servicemanager/issues
- [Online documentation](https://zendframework.github.io/zend-servicemanager)
- [Documentation source files](doc/book/)

## Benchmarks

We provide scripts for benchmarking zend-servicemanager using the
[PHPBench](https://github.com/phpbench/phpbench) framework; these can be
found in the `benchmarks/` directory.

To execute the benchmarks you can run the following command:

```bash
$ vendor/bin/phpbench run --report=aggregate
```
