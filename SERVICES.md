#Core modules services
Modules are responsible for declaring to _Core_ a list of the services they expose to other modules.

For each module declared in ``config/settings.php``, _Core_ will call ``AbstractModule::getServices()`` method. This 
method must return to _Core_ a complete list of services which belong to the module, as an associative array where keys 
are services "names" and values are class names.

Though not mandatory, its a good practice to name services as [module-name].[service-name]. Classes identified by values 
MUST extend `Core\AbstractServiceProvider` abstract class.
```php
[
    "foo.serviceA" => "ServiceA",
    "foo.serviceB" => "ServiceB
]
```
Note that _Core_ will assert that classes identified in values are under the module namespace. In the example above, if 
`Foo` is the module name and namespace, _Core_ will autoload `ServiceA` and `ServiceB` classes using `Foo` namespace 
(eventually loading `Foo\ServiceA` and `Foo\ServiceB` classes as service providers).
