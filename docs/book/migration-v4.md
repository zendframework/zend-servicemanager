# Migration Guide

Migration guide for Zend Service version 4.0.0.

## PSR-11: Container Interface

[`container-interop/container-interop`](https://github.com/container-interop/container-interop)
was officially deprecated in favor of [PSR-11](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md)
on February 13, 2017. As such, all uses of the interop-container interfaces
have been replaced with PSR-11 containers interfaces as follows:
  - `Interop\Container\ContainerInterface` to `Psr\Container\ContainerInterface`,
  - `Interop\Container\Exception\ContainerException` to `Psr\Container\ContainerExceptionInterface`,
  - `Interop\Container\Exception\NotFoundException` to `Psr\Container\NotFoundExceptionInterface`.

Further, installs of `container-interop/container-interop` below version `1.2.0`
is prohibited via Composer's `conflicts` configuration. Version `1.2.0` _does_
extend the PSR-11 interfaces, and is thus still usable.

If your project typehints any `Interop\ContainerInterop\*` interfaces where any
`Zend\ServiceManager\*` classes are expected, you _**must**_ update your code to
expect `Zend\ServiceManager\*` or `Psr\Container\*` classes or interfaces instead.
The latter is preferred, unless your code utilizes any additional functionality
provided by the `Zend\ServiceManager\*` classes that are not declared in the
PSR-11 interfaces.

To do this, use your favorite find-and-replace tool to update the following:
  - `use Interop\Container\ContainerInterface;` -> `use Psr\Container\ContainerInterface;`
  - `use Interop\Container\Exception\ContainerException;` -> `use Psr\Container\ContainerExceptionInterface;`
  - `use Interop\Container\Exception\NotFoundException;` -> `use Psr\Container\NotFoundExceptionInterface;`
    - **Note:** You will also need to replace `ContainerException` with `ContainerExceptionInterface`
      and `NotFoundException` with `NotFoundExceptionInterface` where it is used
      throughout your code. If you _don't_ want to do that, you can include
      `as ContainerException`/`as NotFoundException` in the find-and-replace,
      and any existing use of `ContainerException`/`NotFoundException` will
      continue to work.

> ### Note
>
> If you use fully-qualified class names in your typehints, rather than taking
> advantage of `use` statements, you will need to run additional find-and-replace
> commands to update those class paths within your code. The exact finds and
> replaces to run for those scenarios are not covered in this migration guide, as
> they can vary greatly and are not a generally recommended practice.
