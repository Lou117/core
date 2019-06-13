# You're about to discover *Core* microframework !
*Core* microframework gathers some state-of-the-art PHP recommendations and components ensuring that all painful parts 
of any PHP application are done "the right way". It's up to you to be creative for the rest.
## *Core* is an assembly
*Core* implements and assembles some PHP recommendations and popular components:
- Monolog library ([https://seldaek.github.io/monolog/]()) for PSR-3 compliant logging;
- Guzzle PSR-7 implementation ([https://github.com/guzzle/psr7]()) for server request and response streamlining;
- FastRoute library ([https://github.com/nikic/FastRoute]()) for request routing;
- PSR-15 (HTTP Server Request Handlers) for middleware implementation;
- PSR-11 (Container Interface) added to *Core* `RequestHandlerInterface` implementation.

As such, *Core* is very lightweight, as it implements two of the simplest PSR recommendations ; delegating logging, 
routing, server request and server response building to renowned and bullet-proof libraries what are Monolog, Guzzle and 
FastRoute.
## What do I do with *Core* ?
Whatever you want, from HTTP APIs to websites. *Core* architecture makes no assumption on what you'll gonna build with 
it, it just provide you with some tools easing your way to the fun part of your project: actually coding what will make 
it great, not the boilerplate part.
## Where do I begin ?
Download *Core* skeleton application using [Composer](https://getcomposer.org/) 
(`composer create-project lou117/core-skeleton`): a tutorial will help you through your journey, if you need it !
# *Core* documentation
- [Request lifecycle in *Core*](doc/request_lifecycle.md)
- [Configuration syntax and usage](doc/configuration.md)
- [Routing table syntax and usage](doc/routing.md)
- [Understanding PSR-15 implementation](doc/psr-15_implementation.md)
- [Adding controllers](doc/controllers.md)
- [Migrating from v3.0 to v3.1](doc/migrating_to_v31.md)
