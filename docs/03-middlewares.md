# Middleware chains
As for middleware classes [AbstractMiddlewareChainItem](https://github.com/mbadal/psr15/blob/master/src/Psr15/Middleware/AbstractMiddlewareChainItem.php) from package [delvesoft/psr15](https://github.com/mbadal/psr15) is being used as the base abstract class,
each middleware implementation has to extend it.
A Middleware implementation requires two constructor arguments of a type:
- [ServerRequestFactoryInterface](https://github.com/php-fig/http-factory/blob/master/src/ServerRequestFactoryInterface.php)
- [ResponseFactoryInterface](https://github.com/php-fig/http-factory/blob/master/src/ResponseFactoryInterface.php)

Currently the bundles uses `nyholm/psr7` library for PSR-17 HTTP Factories implementation.
## Factory

## Definition
Each middleware has to be defined as a service in the `services` file.
## Resolving
## Matching