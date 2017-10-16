# Core is a lightweight and pragmatic PHP microframework
*Core* is a very personal view on what a microframework should be. Designed to be ready for duty out of the box, it is
designed to do the painful parts, and let you be creative for the rest.

## General design
*Core* makes you design your application as if it was a operating system with applications, where "operating system" is 
*Core* microframework and modules are "applications".

## Application lifecycle
When *Core* boots it :
- loads its configuration, whether from an array you provide or a filepath ;
- instanciates a logger ;
- "discovers" modules ;
- builds a handy representation of incoming HTTP request ;
- routes this request to the correct module, if any ;
- waits for module response ;
- sends response.

## Modules
Obviouly, all the magic still happen in the modules you will create. **You are completely free about the structure of 
your module**, it's not *Core* microframework business. All *Core* will expect from you is to find a Module class at 
"root" level of your module namespace.

## Install
*Core* is available as a package in Packagist: 
```
composer create-project lou117/core-skeleton
```
Ensure that `cache/` and `log/` directories are allowed for write operations:
```
cd core-skeleton
chmod -R 777 log
chmod -R 777 cache
```
Use `config/settings.php.dist` as your first configuration file:
```
cd config
cp settings.php.dist settings.php
```
Open your favorite browser and navigates to `http://[your-server-name]/ping`. If you see this...
```
{
    "text": "Welcome to Core framework !",
    "link": "https://github.com/lou117/core"
}
```
... then you're done and ready !
