# Middleware chains
As for middleware classes [AbstractMiddlewareChainItem](https://github.com/mbadal/psr15/blob/master/src/Psr15/Middleware/AbstractMiddlewareChainItem.php) from package [delvesoft/psr15](https://github.com/mbadal/psr15) is being used as the base abstract class,
each middleware implementation has to extend it.
A Middleware implementation requires two constructor arguments of a type:
- [ServerRequestFactoryInterface](https://github.com/php-fig/http-factory/blob/master/src/ServerRequestFactoryInterface.php)
- [ResponseFactoryInterface](https://github.com/php-fig/http-factory/blob/master/src/ResponseFactoryInterface.php)

Currently the bundle uses `nyholm/psr7` library for PSR-17 HTTP Factories implementation.
## Factory
To ensure easier creation of middleware instance, you can use [MiddlewareChainItemFactory](https://github.com/mbadal/psr15-symfony-bundle/blob/master/src/Middleware/Factory/MiddlewareChainItemFactory.php)
that is capable of creation of a middleware instance by its class name.
## Chain Definition
Each middleware has to be defined as a service in the `services` file.
The bundle supports two methods of a chain creation:
### Via bundle config
Section [Configuration#Config options](02-configuration.md#config-options)
describes definition of a middleware chain via bundle config.

**Example**:
```yaml
#services.yaml
App\Middleware\MiddlewareAlias1:
    class: App\Middleware\Middleware1
    arguments:
        $serverRequestFactory: '@nyholm.psr7.psr17_factory'
        $responseFactory: '@nyholm.psr7.psr17_factory'

App\Middleware\Middleware2
    arguments:
        $serverRequestFactory: '@nyholm.psr7.psr17_factory'
        $responseFactory: '@nyholm.psr7.psr17_factory'

App\Middleware\Middleware3
    arguments:
        $serverRequestFactory: '@nyholm.psr7.psr17_factory'
        $responseFactory: '@nyholm.psr7.psr17_factory'

#psr15.yaml
psr15:
    ...
    middleware_chains:
        Chain1:
            - 'App\Middleware\MiddlewareAlias1'
            - 'App\Middleware\Middleware2'
        Chain2:
            - 'App\Middleware\Middleware2'
            - 'App\Middleware\Middleware3'
    ...
```
### Via public API
[AbstractMiddlewareChainItem](https://github.com/mbadal/psr15/blob/master/src/Psr15/Middleware/AbstractMiddlewareChainItem.php)
offers two public methods usable for creation of a middleware chain:
* [setNext](https://github.com/mbadal/psr15/blob/master/src/Psr15/Middleware/AbstractMiddlewareChainItem.php#L51)
* [append](https://github.com/mbadal/psr15/blob/master/src/Psr15/Middleware/AbstractMiddlewareChainItem.php#L39)

**Example**:
```yaml
#services.yaml
App\Middleware\MiddlewareAlias1:
    class: App\Middleware\Middleware1
    arguments:
        $serverRequestFactory: '@nyholm.psr7.psr17_factory'
        $responseFactory: '@nyholm.psr7.psr17_factory'
    calls:
        - [ append, ['@App\Middleware\Middleware2']]
        - [ append, ['@App\Middleware\Middleware3']]

App\Middleware\Middleware2
    arguments:
        $serverRequestFactory: '@nyholm.psr7.psr17_factory'
        $responseFactory: '@nyholm.psr7.psr17_factory'

App\Middleware\Middleware3
    arguments:
        $serverRequestFactory: '@nyholm.psr7.psr17_factory'
        $responseFactory: '@nyholm.psr7.psr17_factory'

#psr15.yaml
psr15:
    ...
    middleware_chains:
        Chain1:
            - 'App\Middleware\MiddlewareAlias1'
    ...
```
## Matching
## Resolving
## Caching