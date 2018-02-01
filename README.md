# mxc-servicemanager

Master:
[![Build Status](https://secure.travis-ci.org/mxc-commons/mxc-servicemanager.svg?branch=master)](https://secure.travis-ci.org/mxc-commons/mxc-servicemanager)
[![Coverage Status](https://coveralls.io/repos/github/mxc-commons/mxc-servicemanager/badge.svg?branch=master)](https://coveralls.io/github/mxc-commons/mxc-servicemanager?branch=master)
Develop:
[![Build Status](https://secure.travis-ci.org/mxc-commons/mxc-servicemanager.svg?branch=develop)](https://secure.travis-ci.org/mxc-commons/mxc-servicemanager)
[![Coverage Status](https://coveralls.io/repos/github/mxc-commons/mxc-servicemanager/badge.svg?branch=develop)](https://coveralls.io/github/mxc-commons/mxc-servicemanager?branch=develop)

Compatibility to zend-servicemanager is main design paradigm of this component.
This pacackage was introduced to deliver major progress early.

- File issues at https://github.com/mxc-commons/mxc-servicemanager/issues
- [Online documentation of zend-servicemanager](https://docs.zendframework.com/zend-servicemanager)

## Benchmarks

There are scripts provided for benchmarking zend-servicemanager using the
[PHPBench](https://github.com/phpbench/phpbench) framework; these can be
found in the `benchmarks/` directory.

To execute the benchmarks you can run the following command:

```bash
$ vendor/bin/phpbench run --report=aggregate
```

On Windows phpbench you have to

```bash
$ vendor/bin/phpbench run benchmarks --report=aggregate
```

## Version

Version 0.0.1 created by Frank Hein, maxence operations GmbH


Introduction
------------

MxcRouteGuard restricts access to routes for unauthenticated users. Out of the box MxcRouteGuard works with ZfcUser, however, alternative authentication services
(such as `Zend\Authentication\AuthenticationService`) may be used as long as they provide a `public function hasIdentity()` returning `bool`. MxcRouteGuard is
designed to be very simple and reasonably extendable.

Requirements
------------

* [Zend Framework 2](https://github.com/zendframework/zf2) (latest master)

Features / Goals
----------------

* Allow/Deny access to application routes globally for anonymous users
* Support for ZfcUser Registration Feature (automatically whitelist if enabled)
* Support for ZfcUser redirect feature

Installation
------------

### Main Setup

#### By cloning project

1. Clone this project into your `./vendor/` directory.

#### With composer

1. Add this project in your composer.json:

    ```json
    "require": {
        "mxc-commons/mxc-routeguard": "dev-master"
    }
    ```

2. Now tell composer to download MxcRouteGuard by running the command:

    ```bash
    $ php composer.phar update
    ```

#### Post installation

1. Enabling it in your `application.config.php`file.

    ```php
    <?php
    return array(
        'modules' => array(
            // ...
            'MxcRouteGuard',
        ),
        // ...
    );
    ```

Options
-------

The MxcRouteGuard module has some options to allow you to quickly customize the basic
functionality. After installing MxcRouteGuard, copy
`./vendor/maxence/MxcRouteGuard/config/mxcrouteguard.global.php.dist` to
`./config/autoload/mxcrouteguard.global.php` and change the values as desired.

The following options are available:

- **auth_service** - Name of Authentication Service class to use. Useful for using your own
  authentication service instead of the default ZfcUser. Default is `zfcuser_auth_service`.
- **guard_mode** - Two modes (`white`, `black`) are provided to handle the observed routes list
  (see below). In whitelist mode all routes but the routes provided in the observed routes list
  are protected from anonymous access. In blacklist mode only the routes provided in the observed
  route list are protected from anonymous acceess. Default is `white`.
- **observed_routes** - List of routes to protect from anonymous success (`black` mode) or allow to
  anonymous access (`white` mode). Default: `array()`
- **anonymous_redirect** - If access gets blocked the anonymous user gets redirected to the route
  specified here. Note: The anonymous_redirect route automatically gets whitelisted regardless of
  the guard mode. Default: `zfcuser/login`
- **strategy** - By default MxcRouteGuard redirects attempts to access protected routes by an
  anonymous user. If you want something else but a redirect to happen you may supply an alternative
  strategy here to handle anonymous access.
  Default: `MxcRouteGuard\Service\Strategy\RedirectStrategy`

ZfcUser support
---------------

If ZfcUser is used and the ZfcUser enable_registration flag is set true then MxcRouteGuard
automatically whitelists `zfcuser/register` regardless of the guard mode.

In case a route gets blocked MxcRouteGuard applies a `redirect` parameter to the anonymous_redirect
route which can be used by ZfcUser, if the `use_redirect_parameter_if_present` setting is set `true`.

Note
----

For authenticated users MxcRouteGuard provides full access to all routes. If you need more
detailled control of who can access what route, use [ZfcRbac](https://github.com/ZF-Commons/ZfcRbac) or [BjyAuthorize](https://github.com/bjyoungblood/BjyAuthorize) or similar modules
instead of MxcRouteGuard.

Common use cases for MxcRouteGuard are demo apps which only require a user to be known.

License
-------

MxcRouteGuard is released under the New BSD License. See `license.txt`.

