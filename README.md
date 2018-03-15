# *Core* is a lightweight and pragmatic PHP microframework
*Core* is a very personal view on what a microframework should be. Designed to be ready for duty out of the box, it is
designed to do the painful parts, and let you be creative for the rest.
## General design
*Core* makes no assumptions on how your application is organised. All you need to do is instantiate Core class into your 
front controller, providing with a few settings and a routing table, and you'll be all set.
## Install
*Core* comes with a basic implementation as a package in Packagist: 
```
composer create-project lou117/core-skeleton
```
Please find all setup instructions here: [https://gitlab.com/Lou117/core-skeleton]()
## Usage
Instantiates `Core` with your front-controller, passing both configuration file path and routing table file path as 
constructor arguments, and simply call `Core::run()` method.
```$php
$core = new Core("path/to/your/config/file.php", "path/to/your/routing/table.php");
$core->run();
```
## *Core* settings
You can use, and you will most probably be using, your own configuration file to override *Core* default settings. 
*Core* has very few settings available, mostly attached to *Monolog* (for logging purposes, see 
[https://seldaek.github.io/monolog/]()) and *FastRoute* (for routing purposes, see 
[https://github.com/nikic/FastRoute]()).
```$php
// path/to/your/config/file.php

return [
    "log" => [
        "channel" => "core",
        "class" => ["Monolog\Handler\RotatingFileHandler", ["var/log/log", 10]]
    ],
    "routerCachePath" => "var/cache/fastroute"
];
```
- `log.channel`: is determining log channel name ;
- `log.class`: is an indexed array where first value is *Monolog* handler FQCN, and second value is an array of 
parameters for handler instantiation ;
- `routerCachePath`: full path to *FastRoute* cache file. If given path is not a writable file, *FastRoute* caching will 
be disabled.

*Core* has all of its default settings hard-coded, so PHP array returned by your configuration file can be completely 
empty. However, feel free to override using your configuration file at *Core* instantiation. Anything else you will add 
to your configuration file will be left untouched and wille be fully accessible down the way to controllers.
## *Core* routing
For routing logic, *Core* is using *FastRoute* ([https://github.com/nikic/FastRoute]()). *Core* expects your routing 
table to be formatted as an associative array, where keys are route names, and values are associative arrays:
```$php
// path/to/your/routing/table.php

return [
    "ping": [
        "methods": ["GET"],
        "endpoint": "/ping",
        "controller": "Your\Own\ExampleController::yourFancyMethod"
    ]
];
```
For each route:
- `methods`: is an indexed array where all allowed methods are listed;
- `endpoint`: is the route URL;
- `controller`: is your controller FQCN and method.

All these three key-value pairs are mandatory for any route to be a valid one. A fourth key-value pair can be added, 
giving default arguments to routes that have arguments:
```$php
// path/to/your/routing/table.php

return [
    "ping": [
        "methods": ["GET"],
        "endpoint": "/blog/page/{page_number}",
        "controller": "Your\Own\ExampleController::yourFancyMethod",
        "arguments": [
            "page_number": "1"
        ]
    ]
];
```
Please note that any other key-value pair you will be adding to a route declaration will be fully accessible down the 
way to controllers, under `Lou117\Core\Route::arguments` public property.
## Controllers
In order for *Core* to automatically instantiates and run them, controllers must extend `Lou117\Core\AbstractController` 
class. All you need is to implement the method you declared in your routing table ; this method must return an instance 
of any class extending `Lou117\Core\Http\Response\AbstractResponse`.
```$php
// ExampleController.php
namespace Your\Own;

use Lou117\Core\AbstractController;

class ExampleController extends AbstractController
{
    public function yourFancyMethod(): AbstractResponse
    {
        return new TextResponse("Hello World!");
    }
}
```
## Extending *Core* `AbstractController` class
In almost every case, you will need to implement your own `AbstractController` class, extending default *Core* class.
```$php
// AbstractController.php
namespace Your\Own;

use Lou117\Core\AbstractController as CoreAbstractController;

class AbstractController extends CoreAbstractController
{
    public function __construct(Core $core_instance)
    {
    
    }
    
    public function run(string $controller_method): AbstractResponse
    {
        parent::run($controller_method);
    }
}
```
By re-implementing `AbstractController::__construct()` method, you can use the `Core` instance passed as only parameter to initialize 
anything you need before any endpoint-specific logic. Please keep in mind that what happen in `__construct()` method is 
executed with every HTTP request.

By re-implementing `AbstractController::run()` method, you can execute any code that must be executed with every HTTP 
request but depends on computed route. You can also use this method to abort execution based on specific data attached 
to route or request.
