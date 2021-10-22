`/`[Home](/psr15-symfony-bundle)`/`[1.0](/psr15-symfony-bundle/1.0)`/`[Configuration](/psr15-symfony-bundle/docs/02-configuration.html)

---
**NOTE**

You are not reading the most recent version of this documentation. [2.0](/psr15-symfony-bundle/2.0) is the latest version available.
---

# Configuring Bundle
Configuration for the bundle has to be stored in the `psr15.yaml` file.
## Config structure
After successful installation the actual configuration template can be dumped by running:
```bash
php bin/console debug:config ProfesiaPsr15Bundle
```
The base config structure looks like the following example:
```yaml
psr15:
    use_cache: false
    middleware_chains:
        ChainName:
            - App\Middleware1
            - App\Middleware2

    routing:
        ConditionName:
            middleware_chain: [Chain Name]
            conditions:
                - {route_name: [Route Name]}
                - {path: '/test', method: 'GET|POST'}
            prepend:
                - [Other middleware service name]
                - [Other different middleware service name]
            append:
                - [Yet another middleware service name]
```

## Config options
- `use_cache` (true | false) - whether to use cache for resolved middleware chains
  Disable caching during development, use cache in the production
- `middleware_chains` - array of middleware chain items to be assigned to a single chain
    - `{Chain Name}` - name of the chain to be used in the _routing_ part of the config
        - `{Service alias}` - array of service names
- `routing` - list of conditions
    - `{Condition Name}` - name of the condition
        - `middleware_chain` - name of a desired middleware chain to be used
        - `conditions` - configuration array for assigning of a certain previously referenced
          middleware chain to a specific route/application path with HTTP method
        - `prepend * ` - array of middleware services to be prepended to the referenced middleware chain
        - `append *` - array of middleware services to be appended to the referenced middleware chain

Options marked with * character are fully optional.

## Condition config explained
The bundle currently supports two variants of assigning a specific middleware chain to application:
- by **Route**
- by **Path**

For **Route** variant the FCFS rule will be applied:
conditions with a same definition as an already defined middleware chain will be ignored.

**Path** variant works in a different fashion: each following middleware will be appended to the already registered chain.
In this case the order of the defined rules is important.
### Route
Existing route name defined in your application has to be supplied.
Route name existence will be checked upon registration to middleware resolver.

**Magic**:
- Upon registering condition with route name `*` any route can be matched during the middleware resolving process.
  Condition list has to be empty to accept magic route name, otherwise it will be ignored.
  All the following route name rules will be ignored.
### Path
Application path start at which middleware chain should be triggered has to be supplied.
Condition variant support usage of the `method` key for further specification of the HTTP method.
It is possible to enlist multiple HTTP methods delimited by the `|` character.
If omitted, condition will be triggered on any HTTP method.

**Examples**
```yaml
- {path: '/test'} #any HTTP method will be matched
- {path: '/test', method: 'GET'} #only GET HTTP method will be matched
- {path: '/test', method: 'GET|POST|PUT'} #any of GET,POST,PUT HTTP methods will be matched
# appending to the registered chain
- {path: '/test'} #Middleware1, final chain consists of: Middleware1 for each HTTP method
- {path: '/test', method: 'GET|POST'} #Middleware2, final chain consists of: Middleware1, Middleware2 for GET|POST HTTP method and of: Middleware1 for the remaining HTTP method
```

**Configuration**:
- Path configuration has to start with slash - `/` character.
