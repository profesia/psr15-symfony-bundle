# Upgrading guide
## Table of contents
* [From 1.x to 2.x](#how-to-upgrade-from-1x-to-2x)
## How to upgrade from 1.x to 2.x
2.0.0 is the new major version. The main change made to the library is related to the middleware class definition - 
abstract class [AbstractMiddlewareChainItem](https://github.com/mbadal/psr15/blob/master/src/Psr15/Middleware/AbstractMiddlewareChainItem.php) was dropped
and no longer should be used as an abstract class for middleware. Instead, all middleware classes should implement [PSR Middleware interface](https://github.com/php-fig/http-server-middleware/blob/master/src/MiddlewareInterface.php).
### BC Breaks
* Public methods of `AbstractMiddlewareChainItem` no longer can be used to compose a middleware chain definition.
