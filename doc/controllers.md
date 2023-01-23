# Adding controllers
*Core* does NOT implement controllers as middlewares. Although *Core* provides an `AbstractController` abstract class 
as a suggested base for your controllers, you are free to not use it. The only thing *Core* will ensure is that your 
controller method returns an instance of PSR-7 `ResponseInterface`.

## *Core* `AbstractController` abstract class
*Core* provides `AbstractController` abstract class as a possible base for your controller classes. However, *Core* does 
not enforce you to use it for a very good reason: it does not provide any specific functionality.

The only reason why *Core* provides this class is to explicit an internal behavior of *Core* microframework : it passes
its PSR-11 container to controller constructor.
```php
abstract class AbstractController
{
    public function __construct(Container $core_container)
    {

    }
}
```

## Using *Core* container in controllers
As of *Core* v3.1, *Core* container has *at least* these identifiers populated when passed to a controller class 
constructor (other entries might be added by middlewares):
```php
use Lou117\Core\AbstractController as CoreAbstractController;

abstract class MyAbstractController extends CoreAbstractController
{
    public function __construct(Container $core_container)
    {
        /**
         * By default, Core logger handler is a RotatingFileHandler instance, but this can be change through settings.
         * @var Monolog\Handler\AbstractHandler
         */
        $core_container->get('core.logger');
        
        /**
         * @var GuzzleHttp\Psr7\ServerRequest
         */
        $core_container->get('core.request');
        
        /**
         * @var Lou117\Core\Route
         */
        $core_container->get('core.route');
        
        /**
         * Core uses FastRoute default GroupCountBased dispatcher.
         * @var FastRoute\Dispatcher\GroupCountBased
         */
        $core_container->get('core.router');
        
        /**
         * @var array
         */
        $core_container->get('core.configuration');
    }
}
```
You will most likely want to store *Core* container as a property of you own `AbstractController` class making it 
available for your controllers methods, or store only some information individually: it's totally up to you.

## Adding controller methods bound to a route
When declaring a route in your [routing table](routing.md), controllers are set as FQSEN (Fully Qualified Structural 
Element Name, ex. `MyApp\Controller\MyController::myMethod`). *Core* validates this FQSEN *after* middlewares 
have been instantiated and run, just before your controller is being itself instantiated and run.

Your FQSEN is considered valid when:
- its format is correct (ex. `MyApp\Controller\MyController::myMethod`);
- given class can be autoloaded;
- given method is callable (*Core* uses `method_exists()` function).

Of course, method implementation is up to you, but your method must return an instance of PSR-7 `ResponseInterface`. As 
a dependency of *Core*, Guzzle comes with a `Response` class implementing PSR-7 `ResponseInterface`, but you are free to 
use any PSR-7 implementation you want.
```php
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Lou117\Core\AbstractController as CoreAbstractController;

abstract class MyAbstractController extends CoreAbstractController
{
    public function index(): ResponseInterface
    {
        return new Response(200, [], "Welcome to my website!");
    }
}
```
