# HTTP request lifecycle in *Core*
## Step 1 - Request is streamlined
When calling `Core::__construct()` method in your front controller, Guzzle PSR-7 implementation is used to create a 
PSR-7 compliant `ServerRequest` instance ready to be used across the entire application using *Core* internal PSR-11 
container. You can also provide an instance of `RequestInterface` to `Core::run()` method, as a substitute to internally 
built `ServerRequest` instance (e.g. when testing, but not only).

## Step 2 - Request is routed
When calling `Core::run()` method in your front controller, FastRoute library is used to :
- determine if processed HTTP request URI is registered in your [routing table](routing.md) (resulting in a 404 if 
not) ;
- determine if processed HTTP request method is allowed by your [routing table](routing.md) (resulting in a 405 if 
not) ;
- parse all URI arguments (parts of processed HTTP request URI that contains actionable data) ;

You can refer to [*Core* routing documentation](routing.md) and 
[FastRoute documentation](https://github.com/nikic/FastRoute) to have a full understanding on how *Core* interacts with 
FastRoute and how FastRoute is implemented.

*Core* stores the `Route` instance matching with request parameters in its internal PSR-11 container, under `core.route` 
identifier.

## Step 3 - Diving into middlewares 
Once incoming HTTP request has been routed, each middleware declared in [configuration](configuration.md) is 
instantiated and run. This task eschews to an instance of `RequestHandler` class, implementing PSR-15 
`RequestHandlerInterface`, which give itself as parameter to `MiddlewareInterface::process()` method calls.

As processed request is stored in *Core* internal PSR-11 container, and this container is passed to `ServerRequest` 
instance at instantiation, all middlewares can read from and write to this PSR-11 container, that will also be available 
later in controller.

You can refer to [*Core* PSR-15 implementation details](psr-15_implementation.md) to have a full explanation about how 
*Core* implements PSR-15 recommendation.

## Step 4 - Controller
*Core* does NOT implement controllers as middlewares. Although *Core* provides an `AbstractController` abstract class 
as a suggested base for your controllers, you are free to not use it. The only thing *Core* will ensure is that your 
controller method returns an instance of PSR-7 `ResponseInterface`.

You can refer to [*Core* controller documentation](controllers.md) for more details about how to implement your 
controllers.

## Step 5 - Rising from middlewares
Once controller has been executed, middlewares can still manipulate `ResponseInterface` instance returned by controller 
before it being sent to HTTP client.
