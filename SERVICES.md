#Core modules services
Services can be declared for each module in `config/settings.php`, using "services" entry of module declaration.

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
