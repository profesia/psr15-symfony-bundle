# Console
The bundle offers two console commands as part of its infrastructure:
* [ListMiddlewareRulesCommand](../src/Console/Command/ListMiddlewareRulesCommand.php)
* [WarmUpMiddlewareCacheCommand](../src/Console/Command/WarmUpMiddlewareCacheCommand.php)

## ListMiddlewareRulesCommand
**Description**: Lists all registered middleware chains routing rules

**Name**: profesia:psr15:middleware:list-rules

**Sample output**:
```
+------------+------------------------------------ Route rules ---------------------+----------------------------+
| Route name | HTTP method                                                          | Middleware list            |
+------------+----------------------------------------------------------------------+----------------------------+
| index      | GET | POST | PUT | DELETE | HEAD | CONNECT | OPTIONS | TRACE | PATCH | App\Middleware\Middleware1 |
|            |                                                                      | App\Middleware\Middleware2 |
|            |                                                                      | App\Middleware\Middleware3 |
|            |                                                                      | App\Middleware\Middleware4 |
|            |                                                                      | App\Middleware\Middleware5 |
+------------+----------------------------------------------------------------------+----------------------------+
| test       | GET | POST                                                           | App\Middleware\Middleware1 |
|            |                                                                      | App\Middleware\Middleware2 |
|            |                                                                      | App\Middleware\Middleware3 |
|            |                                                                      | App\Middleware\Middleware6 |
+------------+----------------------------------------------------------------------+----------------------------+
+--------------+------------------------------------ Path rules ----------------------+----------------------------+
| Path pattern | HTTP Method                                                          | Middleware chain           |
+--------------+----------------------------------------------------------------------+----------------------------+
| /test        | GET | POST | PUT | DELETE | HEAD | CONNECT | OPTIONS | TRACE | PATCH | App\Middleware\Middleware1 |
|              |                                                                      | App\Middleware\Middleware2 |
|              |                                                                      | App\Middleware\Middleware3 |
|              |                                                                      | App\Middleware\Middleware4 |
+--------------+----------------------------------------------------------------------+----------------------------+
```

## WarmUpMiddlewareCacheCommand
**Description**: Warms up middleware cache

**Name**: profesia:psr15:middleware:warm-up

**Sample output**:
```
+-------+-------------+-------------+----------------------------+
| Route | Static Path | HTTP method | Middleware chain items     |
+-------+-------------+-------------+----------------------------+
| index | /abcd       | GET         | App\Middleware\Middleware1 |
|       |             |             | App\Middleware\Middleware2 |
|       |             |             | App\Middleware\Middleware3 |
|       |             |             | App\Middleware\Middleware4 |
|       |             |             | App\Middleware\Middleware5 |
+-------+-------------+-------------+----------------------------+
| index | /abcd       | POST        | App\Middleware\Middleware1 |
|       |             |             | App\Middleware\Middleware2 |
|       |             |             | App\Middleware\Middleware3 |
|       |             |             | App\Middleware\Middleware4 |
|       |             |             | App\Middleware\Middleware5 |
+-------+-------------+-------------+----------------------------+
| index | /abcd       | PUT         | App\Middleware\Middleware1 |
|       |             |             | App\Middleware\Middleware2 |
|       |             |             | App\Middleware\Middleware3 |
|       |             |             | App\Middleware\Middleware4 |
|       |             |             | App\Middleware\Middleware5 |
+-------+-------------+-------------+----------------------------+
| index | /abcd       | DELETE      | App\Middleware\Middleware1 |
|       |             |             | App\Middleware\Middleware2 |
|       |             |             | App\Middleware\Middleware3 |
|       |             |             | App\Middleware\Middleware4 |
|       |             |             | App\Middleware\Middleware5 |
+-------+-------------+-------------+----------------------------+
| index | /abcd       | HEAD        | App\Middleware\Middleware1 |
|       |             |             | App\Middleware\Middleware2 |
|       |             |             | App\Middleware\Middleware3 |
|       |             |             | App\Middleware\Middleware4 |
|       |             |             | App\Middleware\Middleware5 |
+-------+-------------+-------------+----------------------------+
| index | /abcd       | CONNECT     | App\Middleware\Middleware1 |
|       |             |             | App\Middleware\Middleware2 |
|       |             |             | App\Middleware\Middleware3 |
|       |             |             | App\Middleware\Middleware4 |
|       |             |             | App\Middleware\Middleware5 |
+-------+-------------+-------------+----------------------------+
| index | /abcd       | OPTIONS     | App\Middleware\Middleware1 |
|       |             |             | App\Middleware\Middleware2 |
|       |             |             | App\Middleware\Middleware3 |
|       |             |             | App\Middleware\Middleware4 |
|       |             |             | App\Middleware\Middleware5 |
+-------+-------------+-------------+----------------------------+
| index | /abcd       | TRACE       | App\Middleware\Middleware1 |
|       |             |             | App\Middleware\Middleware2 |
|       |             |             | App\Middleware\Middleware3 |
|       |             |             | App\Middleware\Middleware4 |
|       |             |             | App\Middleware\Middleware5 |
+-------+-------------+-------------+----------------------------+
| index | /abcd       | PATCH       | App\Middleware\Middleware1 |
|       |             |             | App\Middleware\Middleware2 |
|       |             |             | App\Middleware\Middleware3 |
|       |             |             | App\Middleware\Middleware4 |
|       |             |             | App\Middleware\Middleware5 |
+-------+-------------+-------------+----------------------------+
+-------+-------------+-------------+----------------------------+
| test  | /test       | GET         | App\Middleware\Middleware1 |
|       |             |             | App\Middleware\Middleware2 |
|       |             |             | App\Middleware\Middleware3 |
|       |             |             | App\Middleware\Middleware6 |
+-------+-------------+-------------+----------------------------+
| test  | /test       | POST        | App\Middleware\Middleware1 |
|       |             |             | App\Middleware\Middleware2 |
|       |             |             | App\Middleware\Middleware3 |
|       |             |             | App\Middleware\Middleware6 |
+-------+-------------+-------------+----------------------------+
```
### Warning
When defining routes, use of the key `methods` to limit available HTTP methods is advised.
If there are none stated, the command resolves middleware to all HTTP methods.
This can dramatically increase size of cache.