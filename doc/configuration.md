# Using *Core* configuration

## File location
*Core* configuration can be located anywhere : as *Core* is designed to make as few assumptions as possible, path to 
configuration file is a parameter to `Core::__construct()` method called in your front controller, 
[as well as path to routing table](routing.md).

## File format
*Core* expects configuration file to be a PHP script returning an array, that can be empty:
```php
<?php
/* MyApplication settings */
return [];
```

## File content
At its most basic, ***Core* configuration array can be completely empty**: *Core* has its most important configuration 
directives defaulted internally. But for reference, as of *Core* v3.1, here are default values for *Core* settings and 
a description for each:
```php
<?php
return [
    // All logging-related settings.
    "logger" => [
        /* 
            Monolog channel (https://seldaek.github.io/monolog/doc/01-usage.html#leveraging-channels) used by Core to 
            identify its own records.
        */ 
        "channel" => "core",
        /* 
            FQCN for Monolog handler 
            (https://seldaek.github.io/monolog/doc/02-handlers-formatters-processors.html#handlers) class that must be 
            used for Core internal logging, and parameters for this class constructor as an indexed array.
        */
        "class" => ["Monolog\Handler\RotatingFileHandler", ["var/log/log", 10]]
    ],
    // Middleware sequence, as an indexed array of FQCNs designating classes that implements MiddlewareInterface.
    "mw-sequence" => [],
    // All routing-related settings.
    "router" => [
        // Prefix for all endpoints declared in routing table (be careful with extra slashes !).
        "prefix" => "",
        // Define an alternative routing table parser, or use *Core* default one
        "parser" => NestedTableParser::class,
        "cache" => [
            // Whether FastRoute cache (https://github.com/nikic/FastRoute#caching) must be enabled or not.
            "enabled" => true,
            // Path to FastRoute cache file.
            "path" => "var/cache/fastroute"
        ]
    ],
    /* 
        An FQCN designating a class that implements ResponseInterface to be used by Core when no route has been found 
        for incoming HTTP request (404).
    */
    "httpNotFoundResponse" => null,
    /* 
        An FQCN designating a class that implements ResponseInterface to be used by Core when incoming HTTP request 
        method is not allowed for matching route in routing table (405).
    */
    "httpNotAllowedResponse" => null
];
```
Some additional comments:
- `logger.channel`: *Core* internal logger is identified as `core.logger` in Core internal PSR-11 container, so you can 
be fully aware that using this logger, your records will be indistinguishable from those written by *Core* internals. To 
be able to identify your records, you must instantiate a new logger (with a middleware or using 
`AbstractController::__construct()`) and register it in *Core* PSR-11 container.
- `mw-sequence`: middlewares designated here will be instantiated and run **in given order**. See 
[*Core* PSR-15 implementation details](psr-15_implementation.md) to know how *Core* implements PSR-15 recommendation.
- `router.parser`: allows for [using an alternative routing table parser](creating_routing_table_parser.md)
- `router.cache`: FastRoute caching will be used **only** if `router.cache.enabled` is a true-ish value **and** if 
`router.cache.path` designates a writable file.
- `httpNotFoundResponse`: *Core* **ensures** that your custom response will carry a `404` HTTP code.
- `httpNotAllowedResponse`: *Core* **ensures** that your custom response will carry a `405` HTTP code with `Allow` 
header containing a list of valid methods for matching route (complying with RFC2616 10.4.6).
- 
## Adding custom configuration directives to *Core* configuration file
Configuration file content is stored *as is* in *Core* PSR-11 internal container, meaning that you don't need your own 
configuration file. Just add whatever directive you want to configuration array, and it will be available throughout the 
entire application, from middlewares to controllers.

Just remember that *Core* applies an `array_replace_recursive` on its default configuration, meaning that you must be 
careful in choosing a key for an application-specific directive.

