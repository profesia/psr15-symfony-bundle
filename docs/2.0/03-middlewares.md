`/`[Home](/psr15-symfony-bundle)`/`[2.0](/psr15-symfony-bundle/docs/2.0)`/`[Middlewares](/psr15-symfony-bundle/docs/2.0/03-middlewares.html)

# Middleware chains
As for middleware classes [MiddlewareInterface](https://github.com/php-fig/http-server-middleware/blob/master/src/MiddlewareInterface.php) from package [psr/http-server-middleware](https://github.com/php-fig/http-server-middleware) is being 
used as the base contract - interface. Each middleware implementation has to implement it. There are no other demands on a middleware classes.
Each 3rd party middleware implementation of **MiddlewareInterface** is capable of being used in a middleware chain.
## Chain Definition
Each middleware has to be defined as a service in the `services` file.
The bundle supports one method of a chain creation:
### Via bundle config
Section [Configuration#Config options](02-configuration.md#config-options)
describes definition of a middleware chain via bundle config.

**Example**:
```yaml
#services.yaml
App\Middleware\MiddlewareAlias1:
    class: App\Middleware\Middleware1
    arguments:
      #...

App\Middleware\Middleware2:
  arguments:
    #...

App\Middleware\Middleware3:
  arguments:
    #...

#psr15.yaml
psr15:
    #...
    middleware_chains:
        Chain1:
            - 'App\Middleware\MiddlewareAlias1'
            - 'App\Middleware\Middleware2'
        Chain2:
            - 'App\Middleware\Middleware2'
            - 'App\Middleware\Middleware3'
    #...
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
After potential match was found, HTTP method is beign checked.

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
