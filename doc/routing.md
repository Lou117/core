# Routing HTTP requests in *Core*
*Core* takes full advantage of popular library [FastRoute](https://github.com/nikic/FastRoute) for routing. And instead 
of spreading your route declarations across your controllers using annotations, *Core* suggests you to bring together 
all your routes into a PHP array in a specific file, called the *routing table*.
## Routing table location
*Core* routing table can be located anywhere : as *Core* is designed to make as few assumptions as possible, path to 
routing table file is a parameter to `Core::__construct()` method called in your front controller, 
[as well as path to settings](settings.md).
## File format
*Core* expects routing table file to be a PHP script returning an array, that can be empty:
```php
<?php
/* MyApplication routing table */
return [];
```
## File content
At its most basic, ***Core* routing table array can be completely empty**: your application will simply return a `404` 
HTTP response to all of incoming HTTP requests. To add a new route declaration to your routing table, just add a new 
key-value pair to routing table array, with this format:
```php
<?php
/* MyApplication routing table */
return [
    "index" => [
        "methods" => ["GET"],
        "endpoint" => "/",
        "controller" => "MyApplication\IndexController::index"
    ]
];
```
Each route is *de facto* identified by a unique key (in this case `index`). Each route declaration is an associative 
array, containing at least three key-value pairs:
- `methods` is an indexed array containing all of allowed methods for declared route. If an incoming HTTP request 
matches with `endpoint` pattern but does not carry an allowed method, *Core* will return a `405` HTTP response. In case 
you forget to add *at least* one allowed HTTP method, *Core* will ignore your route declaration and warn you using
 *Core* log.
- `endpoint` is a pattern in the format handled by FastRoute library. Actionable data (a.k.a. "arguments") will be
declared using brackets (`/user/{id}`), and a regex can be added to be more precise and/or more safe (`/user/{id:\d+}` 
will match only if `{id}` is a digit). Please refer to FastRoute documentation to know all about route patterns 
([https://github.com/nikic/FastRoute#defining-routes]()). In case you provide an empty (or all-whitespace) string, 
*Core* will ignore your route declaration and warn you using *Core* log.
- `controller` is a FQSEN (Fully Qualified Structural Element Name) designating a controller method for this route. This 
method must return an instance of `ResponseInterface`. Given FQSEN is not "validated" (checked for being callable) until 
all middlewares have been instantiated and run, meaning that for a given route declaration, you can set `controller` 
value to `null` **if you know that one of the middleware will return a response for this route before any controller 
being called**. With that being said, **this is not a very good practice**, because you will be transforming a middleware into a controller, 
and PSR-15 stands that middlewares are meant for **common** request and response processing.
## Using `Route` instance
When routing table is loaded, *Core* transforms each **valid** route declaration into an instance of `Route` class. Once 
FastRoute has done its job, only the relevant `Route` instance is stored in *Core* internal PSR-11 container, under 
`route` identifier. A typical `Route` instance contains :
- route's name (`Route::name`), the unique identifier for given route in routing table;
- allowed HTTP methods for this route (`Route::methods`), as registered in routing table;
- route's endpoint (`Route::endpoint`), in a format compatible with FastRoute standard parser;
- bound controller for this route (`Route::controller`), as an FQSEN (Fully Qualified Structural Element Name, ex. 
`MyApplication\MyController::myMethod`);
## Defining default values for endpoint actionable data (a.k.a. "arguments")
For optional URI "arguments" (enclosed in `[]`), you can define default values within your route declaration:
```php
<?php
/* MyApplication routing table */
return [
    "index" => [
        "methods" => ["GET"],
        "endpoint" => "/",
        "controller" => "MyApplication\IndexController::index"
    ],
    "displayArticle" => [
        "methods" => ["GET"],
        "endpoint" => "/articles[/{id:\d+}]",
        "controller" => "MyApplication\ArticleController::get",
        "arguments" => [
            "id" => "last"
        ]
    ]
];
```
In this example, if `/articles` endpoint is requested without any ID, `Route::arguments` property will be hydrated 
using default value.
## Adding custom data to route declaration
You are absolutely free to add any data to your route declaration:
```php
<?php
/* MyApplication routing table */
return [
    "index" => [
        "methods" => ["GET"],
        "endpoint" => "/",
        "controller" => "MyApplication\IndexController::index"
    ],
    "displayArticle" => [
        "methods" => ["GET"],
        "endpoint" => "/articles[/{id:\d+}]",
        "controller" => "MyApplication\ArticleController::get",
        "arguments" => [
            "id" => "last"
        ]
    ],
    "blog" => [
        "methods" => ["GET"],
        "endpoint" => "/blog",
        "controller" => "MyApplication\BlogController::get",
        "foo" => "bar"
    ]
];
```
All data in routing table declaration that is not used by FastRoute nor *Core* is kept in `Route::attributes` property 
to be at your disposal anywhere in your application.
