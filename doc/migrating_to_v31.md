# Migrating to Core v3.1
Although being a minor release, *Core* v3.1 introduces some breaking changes when compared to *Core* v3.0. Here you can 
find all these breaking changes, and strategies to ensure your transition to v3.1 is as smooth as possible.
## Routing table parsing changes
This is the most obvious change in *Core* v3.1 : *Core* default routing table parser now handles nested routes, thus 
routing table expected format has changed.

To ease your transition, *Core* v3.1 comes with `LegacyTableParser` class, ensuring backward compatibility with *Core* 
v3.0 routing table format. Just change your configuration to set `LegacyTableParser` class as routing table parser:
```php
<?php
// MyApplication configuration
return [
    "router" => [
        "parser" => \Lou117\Core\Routing\LegacyTableParser::class
    ]
];
```
You can use [documentation](using_default_routing_table_parser.md) to update your routing table format before reverting 
your app configuration to default *Core* routing table parser.
## *Core* PSR-11 container identifiers changes
With *Core* v3.1, all identifiers used by *Core* in its internal PSR-11 container are prefixed (or namespaced) with 
`core.`. Below is a matching table:
|v3.0|v3.1|
|----|----|
|settings|core.configuration|
|request|core.request|
|router|core.router|
|route|core.route|
|core-logger|core.logger|



