#Core routing handling
_Core_ is built with a simple idea: once some basic tasks are done, everything belong to modules. So modules are 
responsible for declaring to _Core_ what are the routes for which they should be triggered.

For each module declared in ``config/settings.php``, _Core_ will call ``AbstractModule::getRoutes()`` method. This 
method must return to _Core_ a complete list of routes which belong to the module.

_Core_ depends on FastRoute (https://github.com/nikic/FastRoute) for its routing logic, and therefore enjoys the speed
and all the magic of FastRoute. So internally, `AbstractModule::getRoutes()` return will be directly use for FastRoute 
setup.

`AbstractModule::getRoutes()` must return an associative array where keys are route "names" and values are associative 
arrays, containing two key-value pairs: one for endpoint mask, another for allowed HTTP methods for this 
endpoint.
```php
[
    "adminDoSomething" => [
        "endpoint" => "/admin/do-something,
        "methods" => ["GET"]
    ],
    "foobar" => [
        "endpoint" => "/admin/do-another-thing",
        "methods" => ["GET", "POST"]
    ],
    "baz" => [
        "endpoint" => "/do-something-else",
        "methods" => ["DELETE"]
    ]
]
```
