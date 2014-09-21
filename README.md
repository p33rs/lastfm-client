# About:

This is a LastFM client with a built-in cache and rate limiter.

## Usage:
```
    namespace p33rs\LastFM\Client;
    // read our config files
    Config::read('config.dist.php', true);
    Config::read('config.local.php', true);
    // instantiate the client
    try {
        $lastFm = new Client();
    } catch (Exception $e) {
        echo 'configuration error: ' . $e->getMessage();
    }
```

## Todo:

- AUTHENTICATION, YO.
- More storage adapters and a method of choosing which to use.