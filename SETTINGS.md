# Core configuration

## `Core::boot` parameters
`Core::boot` method expects an associative array as only parameter, containing **at least** one key:
```php
Core::boot([
    // REQUIRED, must be an instance of Composer\Autoload\ClassLoader and will be used for modules registering.
    "composerLoader" => null, 
    // OPTIONAL, must be either an associative array or a string.
    "settings" => []
]);
```

## Overriding Core settings
As of v1.4.0, *Core* no longer makes any assertion about its settings location, making it easy to deal with multiple 
environments. In order to override default *Core* settings, developers are now required to provide an associative array 
or a filepath (as a string) with `settings` key of `Core::boot` method array parameter.

If an array is provided, this array will be considered *Core* settings and will override default settings. If a string 
is provided, this string will be considered a filepath. *Core* will check for corresponding file existence (and throw a 
`SettingsNotFoundException` if settings file is not found), and `require()` this file.

In all cases, *Core* will throw a `SettingsInvalidException` if `Core:boot` parameter `settings` key handling is not 
resulting in an array.

## Core settings default values
As of *Core* v1.4.0, below default values are applied to *Core* settings:
```php
[
    // If true, Core\Problem instances are hydrated with exceptions messages and debug trace.
    "debugMode"     => false,
    // If true, sessions are started by Core for every request.
    "startSession"  => false,
    // If provided, URI prefix is removed from incoming request URI before routing.
    "uriPrefix"     => "",
    // Modules to be registered.
    "modules"       => []
]
```
You are NOT required to provide these values in *Core* settings, unless you want to override them, obviously.
 
## Modules
If you want your app to actually DO something, you will have to override `modules` settings key, providing an 
associative array where keys are modules "names" and values are modules "properties":
```php
[
    "Ping" => [
        // REQUIRED, module namespace as provided to Composer.
        "composerNamespace" => "Ping\\",
        // REQUIRED, module namespace root as provided to Composer.
        "composerPath" => "module/Ping/src",
        // OPTIONAL, module services as an associative array where keys are service names and values are classnames.
        "services" => [
            "ping.service" => "PingProvider"
        ]
    ]
]
```
