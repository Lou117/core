# Creating a custom routing table parser
As of *Core* v3.1, using *Core* [settings](settings.md), you can define an alternative routing table parser to be used 
by *Core* to produce the indexed array of `Route` instances that will be used as input for 
[FastRoute](https://github.com/nikic/FastRoute). 

When you define an alternative routing table parser, your class must extends `AbstractRoutingTableParser` abstract 
class.
```php
<?php
abstract class AbstractRoutingTableParser
{
    protected $coreLogger;

    public function setLogger(Logger $core_logger)
    {
        $this->coreLogger = $core_logger;
    }

    abstract public function parse(string $routing_table_path): array;
}
```
As you can see, *Core* injects its internal logger using `setLogger` method, so you can use it for logging purposes in 
your parser logic. The second method is `parse`, which only receives the routing table file path (already validated for 
existence!) as argument, and must return an array.

With PHP, array values type cannot be enforced using return type declarations. Hence, if your parser doesn't return an 
array of `Route` instances, all invalid values will be ignored, with a warning message being written to *Core* log.
