# Console Tools

Starting in 3.2.0, zend-servicemanager began shipping with console tools. This
document details each.

## generate-deps-for-config-factory

```bash
$ ./vendor/bin/generate-deps-for-config-factory
Usage:

  generate-deps-for-config-factory [-h|--help|help] [-i|--ignore-unresolved] <configFile> <className>

Arguments:

  -h|--help|help          This usage message
  -i|--ignore-unresolved  Ignore classes with unresolved direct dependencies.
  <configFile>            Path to a config file for which to generate
                          configuration. If the file does not exist, it will
                          be created. If it does exist, it must return an
                          array, and the file will be updated with new
                          configuration.
  <className>             Name of the class to reflect and for which to
                          generate dependency configuration.


Reads the provided configuration file (creating it if it does not exist),
and injects it with ConfigAbstractFactory dependency configuration for
the provided class name, writing the changes back to the file.
```

This utility will generate dependency configuration for the named class for use
with the [ConfigAbstractFactory](config-abstract-factory.md). When doing so, it
will read the named configuration file (creating it if it does not exist), and
merge any configuration it generates with the return values of that file,
writing the changes back to the original file.

Since 3.2.1, the tool also supports the `-i` or `--ignore-unresolved` flag.
Use these flags when you have typehints to classes that cannot be resolved.
When you omit the flag, such classes will cause the tool to fail with an
exception message. By adding the flag, you can have it continue and produce
configuration. This option is particularly useful when typehints are on
interfaces or resolve to services served by other abstract factories.

## generate-factory-for-class

```bash
$ ./vendor/bin/generate-factory-for-class

Usage:

  ./bin/generate-factory-for-class [-h|--help|help] <className>

Arguments:

  -h|--help|help    This usage message
  <className>       Name of the class to reflect and for which to generate
                    a factory.

Generates to STDOUT a factory for creating the specified class; this may then
be added to your application, and configured as a factory for the class.
```

This utility generates a factory class for the given class, based on the
typehints in its constructor. The factory is emitted to STDOUT, and may be piped
to a file if desired:

```bash
$ ./vendor/bin/generate-factory-for-class \
> "Application\\Model\\AlbumModel" > ./module/Application/src/Model/AlbumModelFactory.php
```

The class generated implements `Zend\ServiceManager\Factory\FactoryInterface`,
and is generated within the same namespace as the originating class.
