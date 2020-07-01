# Configuring Bundle
Configuration for the bundle has to be stored in the `psr15.yaml` file.
## Config structure
After successful installation you can dump the actual configuration by running:
```bash
php bin/console debug:config Psr15Bundle
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
                - {path: 'test', method: 'POST'}
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
        - `prepend * ` - array od middleware items to be prepended to the referenced middleware chain
        - `append *` - array of middleware items to be appended to the referenced middleware chain

Options marked with * character are fully optional.

## Condition config explained
The bundle currently supports 2 variants of assigning a specific middleware chain to application:
- by **Route**
- by **Path**
For both variants the FCFS rule will be applied: 
conditions with a same definition as an already defined middleware chain will be ignored.
### Route
You have to supply existing route name defined in your application. 
Route name existence will be checked upon registration to middleware resolver.

Magic:
- Upon registering condition with route name `*` any route can be matched during the middleware resolving process. 
Condition list has to be empty to accept magic route name, otherwise it will be ignored.
All of the following route name rules will be ignored.
### Path
You have to supply application path start at which middleware chain should be triggered.
Condition variant support usage of the `method` key for further specification of the HTTP method.
If omitted, condition will be triggered on any HTTP method.
