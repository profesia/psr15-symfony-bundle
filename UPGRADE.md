# Upgrading guide
## Table of contents
* [From 1.x to 2.x](#how-to-upgrade-from-1x-to-2x)
## How to upgrade from 1.x to 2.x
2.0.0 is the new major version. The main change made to the library is related to the middleware class definition - 
abstract class [AbstractMiddlewareChainItem](https://github.com/mbadal/psr15/blob/master/src/Psr15/Middleware/AbstractMiddlewareChainItem.php) was dropped
and no longer should be used as an abstract class for middleware. Instead, all middleware classes should implement [PSR Middleware interface](https://github.com/php-fig/http-server-middleware/blob/master/src/MiddlewareInterface.php).
### BC Breaks
* Public methods of `AbstractMiddlewareChainItem` no longer can be used to compose a middleware chain definition.
### Instructions
1. Replace `extends AbstractMiddlewareChainItem` in every middleware class with `implements Psr\Http\Server\MiddlewareInterface` to ensure upgrading of the library will not fail.
2. Upgrade dependency via `composer require profesia/psr15-symfony-bundle:2.0.0`.
3. Get rid of public/protected method usage of `AbstractMiddlewareChainItem` in every middleware class.
