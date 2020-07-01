# Configuring Bundle
Configuration for the bundle has to be stored in the `psr15.yaml` file.
## Config structure
After successful installation you can dump the actual configuration by running
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
- `routing` - list of conditions
    - `{Condition Name}` - name of the condition
        - `middleware_chain` - name of a desired middleware chain to be used
        - `conditions` - configuration array for assigning of a certain previously referenced
        middleware chain to a specific route/application path with HTTP method
        - `prepend * ` - array od middleware items to be prepended to the referenced middleware chain
        - `append *` - array of middleware items to be appended to the referenced middleware chain

Options marked with * character are fully optional.
