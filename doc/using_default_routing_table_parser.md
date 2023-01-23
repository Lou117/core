# Using *Core* default routing table parser (`NestedTableParser`)

## File format
*Core* default `NestedTableParser` expects routing table file to be a PHP script returning an array, that can be empty:
```php
<?php
/* MyApplication routing table */
return [];
```

## File content
At its most basic, ***Core* routing table array can be completely empty**: your application will simply return a `404` 
HTTP response to all incoming HTTP requests. To add a new route declaration to your routing table, just add a new 
key-value pair to routing table array, with this format:
```php
<?php
/* MyApplication routing table */
return [
    "/blog" => [
        "methods" => ["GET"],
        "controller" => "MyApplication\BlogController::all"
    ]
];
```
In this array:
- keys are patterns in the format handled by FastRoute library. Actionable data (a.k.a. "arguments") will be declared 
using brackets (`/user/{id}`), and a regex can be added to be more precise and/or more safe (`/user/{id:\d+}` will match 
only if `{id}` is a digit). Please refer to FastRoute documentation to know all about route patterns 
([https://github.com/nikic/FastRoute#defining-routes]()).
- values are associative arrays with at least two key-value pairs:
    - `methods` is an indexed array containing all of allowed methods for declared route. If an incoming HTTP request 
    matches with endpoint pattern but does not carry an allowed method, *Core* will return a `405` HTTP response. In 
    case you forget to add *at least* one allowed HTTP method, *Core* will ignore your route declaration and warn you 
    using *Core* log.
    - `controller` is a FQSEN (Fully Qualified Structural Element Name) designating a controller method for this route. 
    This method must return an instance of PSR-7 `ResponseInterface`. Given FQSEN is not "validated" (checked for being 
    callable) until all middlewares have been instantiated and run, meaning that for a given route declaration, you can 
    set `controller` value to `null` **if you know that one of the middleware will return a response for this route 
    before any controller being called**. With that being said, **this is not a very good practice**, because you will 
    be transforming a middleware into a controller, and PSR-15 stands that middlewares are meant for **common** request 
    and response processing.

### Defining different controller based on HTTP method
Alternatively, you can define a different controller FQSEN for each allowed method: 
```php
<?php
/* MyApplication routing table */
return [
    "/blog" => [
        "methods" => ["GET"],
        "controller" => "MyApplication\BlogController::all"
    ],
    "/users" => [
        "methods" => ["GET", "POST"],
        "controller" => [
            "GET" => "MyApplication\UserController::all",
            "POST" => "MyApplication\UserController::create",
            "PUT" => "MyApplication\UserController::updateAll" // Ignored
        ]
    ]
];
```
In this example, a request to `GET /users` will trigger `MyApplication\UserController::all` method, although a request 
to `POST /users` will trigger `MyApplication\UserController::create` method. Have you noticed we added a third method 
with a corresponding controller FQSEN? **This declaration will be ignored**, because `PUT` is not one of the allowed 
methods in `methods`.

### Defining default values for endpoint actionable data (a.k.a. "arguments")
For optional URI "arguments" (enclosed in `[]` in patterns), you can define default values within your route 
declaration:
```php
<?php
/* MyApplication routing table */
return [
    "/blog[/{page}]" => [
        "methods" => ["GET"],
        "controller" => "MyApplication\BlogController::all",
        "arguments" => [
            "page" => "last"
        ]
    ],
    "/users" => [
        "methods" => ["GET", "POST"],
        "controller" => [
            "GET" => "MyApplication\UserController::all",
            "POST" => "MyApplication\UserController::create"
        ]
    ]
];
```
In this example, if `/blog` endpoint is requested without any page number, `Route::$arguments` property will be hydrated 
using default value.

### Adding custom data to route declaration
You are absolutely free to add any data to your route declaration:
```php
<?php
/* MyApplication routing table */
return [
    "/blog[/{page}]" => [
        "methods" => ["GET"],
        "controller" => "MyApplication\BlogController::all",
        "arguments" => [
            "page" => "last"
        ],
        "foo" => "bar",
        "bar" => "baz"
    ],
    "/users" => [
        "methods" => ["GET", "POST"],
        "controller" => [
            "GET" => "MyApplication\UserController::all",
            "POST" => "MyApplication\UserController::create"
        ]
    ]
];
```
All data in routing table declaration that is not used by FastRoute nor *Core* is kept in `Route::$attributes` property 
to be at your disposal anywhere in your application.

## Nested routes
As of *Core* v3.1, default `NestedTableParser` handles nested routes:
```php
<?php
/* MyApplication routing table */
return [
    "/blog[/{page}]" => [
        "methods" => ["GET"],
        "controller" => "MyApplication\BlogController::all",
        "arguments" => [
            "page" => "last"
        ],
        "foo" => "bar",
        "bar" => "baz"
    ],
    "/users" => [
        "methods" => ["GET", "POST"],
        "controller" => [
            "GET" => "MyApplication\UserController::all",
            "POST" => "MyApplication\UserController::create"
        ],
        "children" => [
            "/{id:\d+}" => [
                "methods" => ["GET", "PUT", "DELETE"],
                "controller" => [
                    "GET" => "MyApplication\UserController::get",
                    "PUT" => "MyApplication\UserController::update",
                    "DELETE" => "MyApplication\UserController::delete"
                ],
                "foo" => "baz"
            ]
        ],
        "foo" => "bar",
        "bar" => "baz"
    ]
];
```
By adding `children` key to any route entry, you can nest any number of routes, and add children routes to children 
routes with no limitation. Have you noticed the route attributes at parent and child level? **Child routes will receive 
all parent (at all levels) route attributes and arguments**, and can override them.
