# Routing HTTP requests in *Core*
Instead of spreading your route declarations across your controllers using annotations, *Core* suggests you to bring 
together all your routes into a single file. And again, because *Core* is designed to make as few assumptions as 
possible, parsing of this file (called a *routing table*) is up to you... or you can use default *Core* routing table 
parser.

## General considerations

### Routing table location
*Core* routing table can be located anywhere : as *Core* is designed to make as few assumptions as possible, path to 
routing table file is a parameter to `Core::__construct()` method called in your front controller, 
[as well as path to configuration](configuration.md).

### FastRoute input
Ultimately, *Core* will handle an indexed array of `Route` instances, and will feed 
[FastRoute](https://github.com/nikic/FastRoute) with it. But the way this array is built has no importance at all.

#### `Route` class
After your routing table has been parsed, and once FastRoute has done its job, only the relevant `Route` instance is 
stored in *Core* internal PSR-11 container, under `core.route` identifier. A typical `Route` instance contains:
- allowed HTTP methods for this route (`Route::$methods`), as registered in routing table;
- route's endpoint (`Route::$endpoint`), in a format compatible with FastRoute standard parser;
- bound controller for this route (`Route::$controller`), as an FQSEN (Fully Qualified Structural Element Name, ex. 
`MyApplication\MyController::myMethod`);

## Routing documentation by use case
- [Using default *Core* `NestedTableParser`](using_default_routing_table_parser.md)
- [Using legacy (<v3.1) routing table parser](using_legacy_routing_table_parser.md)
- [Creating a custom routing table parser](creating_routing_table_parser.md)
