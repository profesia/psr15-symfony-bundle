`/`[Home](/psr15-symfony-bundle)`/`[Middlewares](/psr15-symfony-bundle/docs/03-middlewares.md)

# Middleware chains
As for middleware classes [AbstractMiddlewareChainItem](https://github.com/mbadal/psr15/blob/master/src/Psr15/Middleware/AbstractMiddlewareChainItem.php) from package [delvesoft/psr15](https://github.com/mbadal/psr15) is being used as the base abstract class,
each middleware implementation has to extend it.
A middleware implementation requires two constructor arguments of a type:
- [ServerRequestFactoryInterface](https://github.com/php-fig/http-factory/blob/master/src/ServerRequestFactoryInterface.php)
- [ResponseFactoryInterface](https://github.com/php-fig/http-factory/blob/master/src/ResponseFactoryInterface.php)

Currently the bundle uses [nyholm/psr7](https://github.com/Nyholm/psr7) library for PSR-17 HTTP Factories implementation.
## Factory
To ensure easier creation of middleware instance, you can use [MiddlewareChainItemFactory](../src/Middleware/Factory/MiddlewareChainItemFactory.php)
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

App\Middleware\Middleware2:
    arguments:
        $serverRequestFactory: '@nyholm.psr7.psr17_factory'
        $responseFactory: '@nyholm.psr7.psr17_factory'

App\Middleware\Middleware3:
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
Matching of an incoming requests takes place in two clasess:
* [RouteNameStrategyResolver](../src/Resolver/Strategy/RouteNameResolver.php)
* [CompiledPathStrategyResolver](../src/Resolver/Strategy/CompiledPathResolver.php)
### RouteNameStrategyResolver
Matching of an incoming request is straightforward in this strategy:
1. Actual route name is being searched in the registered route middleware chains.
   If found, middleware chain is returned.
2. If no middleware chain is registered to route name,
   strategy check whether there is a rule registered to magical route `*`.
   If found, middleware chain is returned.
3. Resolving strategy passes a middleware resolving request to the following strategy.
### CompiledPathStrategyResolver
Matching of an incoming request is based on compiled static prefix of a route.
Strategy tries to match static prefix accurately by checking the most potent rules firstly.
Registered rules are grouped by their string length.
Match is simply calculated by using `strpos` PHP function -
a static prefix has to start with a registered pattern.
After potentional match was found, HTTP method is beign checked.

1. The compiled static prefix of the actual route is fetched.
   Its string length is calculated.
2. Registered rules are being iterated.
   Matching starts at the exact length of the static path of an incoming request.
   If found, middleware chain is returned.
3. If no middleware chain is matched,
   resolving continues by decreasing length of searched patterns,
   patterns with smaller length are being checked.
   If found, middleware chain is returned.
4. Upon reaching length 0 resolving strategy passes a middleware resolving request to the following strategy.

**Example**:
```yaml
#psr15.yaml
psr15:
    ...
    routing:
        Condition1:
            middleware_chain: 'Test1'
            conditions:
                - {path: '/ab'}
                - {path: '/abc'}
                - {path: '/abcd'}
```
The incoming static prefix is `/abde`. Matching has three iterations:
1. Checking pattern `/abcd`. No match found.
2. Checking pattern `/abc`. No match found.
3. Checking pattern `/ab`. Match found.
   Pattern is registered to all [HTTP methods](02-configuration.md#path).
   Middleware chain with name **Test1** is returned.
## Resolving
Resolving of a middleware chain to be used consists of o two steps:
* passing resolving request to **RouteNameStrategyResolver**
* passing resolving request to **CompiledPathStrategyResolver**
* returning [NullMiddleware](../src/Middleware/NullMiddleware.php) on no middleware chain resolved

Each step will return middleware chain and stop resolving on match found.
## Caching
As stated in the [configuration](02-configuration.md#config-options) section,
key `use_cache` is used to enable bundle caching.
Resolving of a middleware chain could have impact on performance (mainly on an extensive middleware and route config).
In production caching of a resolved middleware is advised.
On turned on cache are resolved middleware chains cached in the cache pool `cache.psr15-middleware`
in the similar fashion as a container is being compiled and stored using PhpFilesAdapter.
Location of a cache pool is set according to standard cache settings: `%kernel.cache_dir%/pools`.

Cache pool can be automatically cleared by calling:
```bash
php bin/console cache:clear
```
or
```bash
php bin/console cache:pool:clear cache.psr15-middleware
```