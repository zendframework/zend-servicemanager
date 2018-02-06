# mxc-servicemanager

Master:
[![Build Status](https://secure.travis-ci.org/mxc-commons/mxc-servicemanager.svg?branch=master)](https://secure.travis-ci.org/mxc-commons/mxc-servicemanager)
[![Coverage Status](https://coveralls.io/repos/github/mxc-commons/mxc-servicemanager/badge.svg?branch=master)](https://coveralls.io/github/mxc-commons/mxc-servicemanager?branch=master)
Develop:
[![Build Status](https://secure.travis-ci.org/mxc-commons/mxc-servicemanager.svg?branch=develop)](https://secure.travis-ci.org/mxc-commons/mxc-servicemanager)
[![Coverage Status](https://coveralls.io/repos/github/mxc-commons/mxc-servicemanager/badge.svg?branch=develop)](https://coveralls.io/github/mxc-commons/mxc-servicemanager?branch=develop)

Compatibility to zend-servicemanager is main design paradigm of this component.

- File issues at https://github.com/mxc-commons/mxc-servicemanager/issues
- [Online documentation of zend-servicemanager](https://docs.zendframework.com/zend-servicemanager)

## Version

Version 0.0.1 created by Frank Hein, maxence operations GmbH

## Important Note

Currently initial setup of this project is still going on. Please do not download or use as long as this is still part of this file.

## Introduction

mxc-servicemanager is a component featuring an API compatible to [zend-servicemanager  3.3](https://github.com/zendframework/zend-servicemanager "zend-servicemanager").

With mxc-servicemanager we refactored several parts if zend-servicemanager for better performance. This includes configuration and setup, factory caching and service resolution. This pacackage was introduced to deliver major progress early.

A major design constraint is zend-servicemanager compatibility. All changes applied to mxc-servicemanager are proposed to the zend-servicemanager project also via pull request. Changed master and develop branches of zend-servicemanager will get merged into mxc-servicemanager asap after release. Asap can mean several days later.

Our motivation to do this comes out of our project portfolio management on one hand (we need a fast service manager for other projects), our commitment to Open Source and the power of sharing on the other hand, and from economical constraints: We are not strong or big enough to work on masses of libraries at the same time, so we have to be focussed (this on the third hand ;).

For highly focussed development approaches (currently service manager is one out of just two for us) the approval process's speed of the parent project is too slow to enable continous work. We don't have the money to spend it on waiting. So this project is our way to be able to apply progress continously. If our quality assurance says go, we go. This project is kind of a pressure relief valve. We need it to be able to invest in this approach at all.

Features / Goals
----------------

* Speed up service manager configuration via configure() (currently 3x faster)
* Speed up service manager configuration via the APIs:
	* addAbstractFactory
	* addDelegator
	* addInitializer
	* mapLazyService
	* setAlias
	* setFactory
	* setInvokableClass
	* setService
	* setShared
* Speed up service delivery for
	* aliases
	* delegators
	* invokables
	* abstract factories

Goal of this approach is to exploit PHP capabilities as far as possible for performance enhancements without giving up on backwards compatibility to
zend-servicemanager 3.3.2 (currently). We are working on optimizing the PHP implementation in order to find out what the particular requirements for
maximum speed actually are. Another thing we want to learn about is how to streamline service manager configuration in order to ease comprehension and
effectivity.

When basic work is done, there is a chance, that we will establish another fork of zend-servicemanager, which will not claim to be backwards compatible in every point. BC will be important, but not everything for this fork. This project will base on the zend-servicemanager dev-4.0 branch as long as this is sufficiently maintained. Whether we will actually do this will depend on the feedback we get. If there are no discussions, feature requests and such, there will be no need to do that in the public. Preliminary project name is mxc-servicemanager-x1.

Based on what we learn we plan to provide a PHP core component or extension library implemented in C, which will combine the functionality and
compatibility of the PHP implementation with the performance of a C implementation. Work on that will not start before 07-2018. Please do not expect visible or stable results in 2018. Preliminary project name is mxc-servicemanager-x2.

##Installation

This component is meant as a transparent replacement of zend-servicemanager. Some Zend Framework components feature hard wired wired dependencies to `Zend\ServiceManager`. To enable to replace zend-servicemanager with mxc-servicemanager, latter is defined in the same namespace `Zend\ServiceManager`.

### Via Packagist

Currently Packagist does not support the provision of projects which occupy namespaces which are already occupied by existing libraries. In our case it's `Zend\ServiceManager` which is used by `zend-servicemanager` also and originally.
So we are sorry not to be able to support direct installation via composer without requirement for afterwards customization currently for zend-servicemanager replacement. We apologize for any inconveniences.

### Use as stand-alone component

mxc-servicemanager has to live in the `Zend\ServiceManager`namespace to allow transparent replacement. We supply this package using namespaces where the string 'Zend' is replaced by 'Mxc'. If you like to use this package aa is (i.e. without transparent replacement of zend-servicemanager) you can use it as is by

1. Add this project to your composer.json:

    ```json
    "require": {
        "mxc-commons/mxc-servicemanager": "dev-master"
    }
    ```

2. Now tell composer to download mxc-servicemanager by running the command:

    ```bash
    $ composer update
    ```

mxc-servicemanager is now available via `Mxc\ServiceManager\ServiceManager`. This will not integrate mxc-servicemanager to Zend applications.

### Use as replacement for zend-servicemanager

If you want to use mxc-servicemanager as a replacement for zend-servicemanager in zendframework dependent projects (expressive, ...), you have to do a bit more, sorry.

1. Follow the steps of 'Use as stand-alone component' described above.
2. Run a replace on all files of `vendors\maxence\mxc-servicemanager` to change 'Mxc' to 'Zend'.
3. Remove zend-servicemanager from your composer dependencies.

We will provide an after-xxx script to do that automatically for you asap.

## Copyright Acknowledgement

Other than the parent project zend-servicemanager this library will acknowledge your copyright on significant changes. If you decide to invest your time and money contributing to this project, your copyright will be maintaned. Please add your copyright notice to copyrights.md specifying the particular things you claim copyright for in `copyrights.md`.

Please consider if your planned copyright claims are valid before you claim. For example, we consider a claim for copyright regarding a global search & replace of `Interop\container` with `psr-11\container` as not appropriate. But we will maintain inappropriate claims also, if you provide a copyright description which is detailed on every level.

The only restriction for copyright claimes is that you license the things you supply under the New BSD License, the license under which this library is generally provided.

##License

mxc-servicemanager is released under the New BSD License. See `license.txt`.

## Benchmarks

There are scripts provided for benchmarking zend-servicemanager using the
[PHPBench](https://github.com/phpbench/phpbench) framework; these can be found in mxc-servicemanager also
in the `benchmarks/` directory.

To execute the benchmarks you can run the following command:

```bash
$ vendor/bin/phpbench run --report=aggregate
```

On Windows phpbench you have to

```bash
$ vendor/bin/phpbench run benchmarks --report=aggregate
```

## Benchmark results

For your convenience you will find benchmark comparisons of zend-servicemanager:master and mxc-servicemanager:master. This section will be updated as new versions come up on either side.

	$ vendor\bin\phpbench report --file=..\master.FetchNewServiceManager.xml --file=..\PR231.FetchNewServiceManager.xml --report=compare
	benchmark: FetchNewServiceManagerBench
	+----------------------------------+-------------------+------------------+
	| subject                          | suite:master:mean | suite:PR231:mean |
	+----------------------------------+-------------------+------------------+
	| benchFetchServiceManagerCreation | 878.050µs         | 287.376µs        |
	+----------------------------------+-------------------+------------------+

	$ vendor\bin\phpbench report --file=..\master.all.xml --file=..\PR231.all.xml --report=compare
	benchmark: FetchCachedServicesBench
	+----------------------------------+-------------------+------------------+
	| subject                          | suite:master:mean | suite:PR231:mean |
	+----------------------------------+-------------------+------------------+
	| benchFetchFactory1               | 0.452µs           | 0.435µs          |
	| benchFetchInvokable1             | 0.473µs           | 0.454µs          |
	| benchFetchService1               | 0.457µs           | 0.437µs          |
	| benchFetchAlias1                 | 0.458µs           | 0.440µs          |
	| benchFetchRecursiveAlias1        | 0.474µs           | 0.451µs          |
	| benchFetchRecursiveAlias2        | 0.468µs           | 0.450µs          |
	| benchFetchAbstractFactoryService | 2.450µs           | 2.471µs          |
	+----------------------------------+-------------------+------------------+

	benchmark: FetchNewServiceUsingConfigAbstractFactoryAsFactoryBench
	+-------------------------------------+-------------------+------------------+
	| subject                             | suite:master:mean | suite:PR231:mean |
	+-------------------------------------+-------------------+------------------+
	| benchFetchServiceWithNoDependencies | 5.042µs           | 4.482µs          |
	| benchBuildServiceWithNoDependencies | 4.613µs           | 4.239µs          |
	| benchFetchServiceDependingOnConfig  | 5.744µs           | 5.061µs          |
	| benchBuildServiceDependingOnConfig  | 5.306µs           | 4.813µs          |
	| benchFetchServiceWithDependency     | 5.681µs           | 5.046µs          |
	| benchBuildServiceWithDependency     | 5.210µs           | 4.798µs          |
	+-------------------------------------+-------------------+------------------+

	benchmark: FetchNewServiceUsingReflectionAbstractFactoryAsFactoryBench
	+-------------------------------------+-------------------+------------------+
	| subject                             | suite:master:mean | suite:PR231:mean |
	+-------------------------------------+-------------------+------------------+
	| benchFetchServiceWithNoDependencies | 3.963µs           | 3.490µs          |
	| benchBuildServiceWithNoDependencies | 3.537µs           | 3.297µs          |
	| benchFetchServiceDependingOnConfig  | 7.089µs           | 6.745µs          |
	| benchBuildServiceDependingOnConfig  | 6.650µs           | 6.610µs          |
	| benchFetchServiceWithDependency     | 8.432µs           | 8.160µs          |
	| benchBuildServiceWithDependency     | 7.960µs           | 7.895µs          |
	+-------------------------------------+-------------------+------------------+

	benchmark: FetchNewServiceViaConfigAbstractFactoryBench
	+-------------------------------------+-------------------+------------------+
	| subject                             | suite:master:mean | suite:PR231:mean |
	+-------------------------------------+-------------------+------------------+
	| benchFetchServiceWithNoDependencies | 5.489µs           | 5.112µs          |
	| benchBuildServiceWithNoDependencies | 4.922µs           | 4.743µs          |
	| benchFetchServiceDependingOnConfig  | 6.143µs           | 5.744µs          |
	| benchBuildServiceDependingOnConfig  | 5.601µs           | 5.412µs          |
	| benchFetchServiceWithDependency     | 6.122µs           | 5.742µs          |
	| benchBuildServiceWithDependency     | 5.564µs           | 5.363µs          |
	+-------------------------------------+-------------------+------------------+

	benchmark: FetchNewServiceViaReflectionAbstractFactoryBench
	+-------------------------------------+-------------------+------------------+
	| subject                             | suite:master:mean | suite:PR231:mean |
	+-------------------------------------+-------------------+------------------+
	| benchFetchServiceWithNoDependencies | 3.434µs           | 3.273µs          |
	| benchBuildServiceWithNoDependencies | 2.919µs           | 2.991µs          |
	| benchFetchServiceDependingOnConfig  | 6.766µs           | 6.680µs          |
	| benchBuildServiceDependingOnConfig  | 6.221µs           | 6.402µs          |
	| benchFetchServiceWithDependency     | 8.095µs           | 7.994µs          |
	| benchBuildServiceWithDependency     | 7.555µs           | 7.694µs          |
	+-------------------------------------+-------------------+------------------+

	benchmark: FetchNewServicesBench
	+----------------------------------+-------------------+------------------+
	| subject                          | suite:master:mean | suite:PR231:mean |
	+----------------------------------+-------------------+------------------+
	| benchFetchFactory1               | 2.820µs           | 2.667µs          |
	| benchBuildFactory1               | 2.395µs           | 2.200µs          |
	| benchFetchInvokable1             | 3.315µs           | 2.477µs          |
	| benchBuildInvokable1             | 2.620µs           | 2.060µs          |
	| benchFetchService1               | 0.455µs           | 0.444µs          |
	| benchFetchFactoryAlias1          | 2.454µs           | 2.223µs          |
	| benchBuildFactoryAlias1          | 2.461µs           | 2.249µs          |
	| benchFetchRecursiveFactoryAlias1 | 2.475µs           | 2.259µs          |
	| benchBuildRecursiveFactoryAlias1 | 2.490µs           | 2.252µs          |
	| benchFetchRecursiveFactoryAlias2 | 2.497µs           | 2.255µs          |
	| benchBuildRecursiveFactoryAlias2 | 2.473µs           | 2.247µs          |
	| benchFetchAbstractFactoryFoo     | 2.407µs           | 2.411µs          |
	| benchBuildAbstractFactoryFoo     | 1.947µs           | 1.985µs          |
	+----------------------------------+-------------------+------------------+

	benchmark: HasBench
	+-------------------------+-------------------+------------------+
	| subject                 | suite:master:mean | suite:PR231:mean |
	+-------------------------+-------------------+------------------+
	| benchHasFactory1        | 0.526µs           | 0.535µs          |
	| benchHasInvokable1      | 0.603µs           | 0.578µs          |
	| benchHasService1        | 0.482µs           | 0.518µs          |
	| benchHasAlias1          | 0.584µs           | 0.556µs          |
	| benchHasRecursiveAlias1 | 0.605µs           | 0.569µs          |
	| benchHasRecursiveAlias2 | 0.603µs           | 0.565µs          |
	| benchHasAbstractFactory | 0.839µs           | 0.870µs          |
	| benchHasNot             | 0.851µs           | 0.877µs          |
	+-------------------------+-------------------+------------------+

	benchmark: SetNewServicesBench
	+------------------------------------+-------------------+------------------+
	| subject                            | suite:master:mean | suite:PR231:mean |
	+------------------------------------+-------------------+------------------+
	| benchSetService                    | 2.027µs           | 0.654µs          |
	| benchSetFactory                    | 4.350µs           | 1.229µs          |
	| benchSetAlias                      | 11.946µs          | 1.917µs          |
	| benchOverrideAlias                 | 36.493µs          | 1.929µs          |
	| benchSetInvokableClass             | 5.359µs           | 0.612µs          |
	| benchAddDelegator                  | 2.090µs           | 0.728µs          |
	| benchAddInitializerByClassName     | 2.473µs           | 1.490µs          |
	| benchAddInitializerByInstance      | 1.764µs           | 0.910µs          |
	| benchAddAbstractFactoryByClassName | 3.488µs           | 2.436µs          |
	| benchAddAbstractFactoryByInstance  | 3.118µs           | 2.043µs          |
	+------------------------------------+-------------------+------------------+