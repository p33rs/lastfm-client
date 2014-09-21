# About:
This is a LastFM client with a built-in cache and rate limiter.

## Setup:
Requires Mongo, Composer. In the future, may add support for more cache storage.
`composer install` and set up database parameters in `config.local.php`, using `config.local.php.example` as a template.

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

## Authentication:
First of all, the official auth docs are here: http://www.last.fm/api/webauth
You are responsible for creating a callback url and securely storing your users' session keys.
On your callback url, you can use this client to create a session key. The code will look like this:
```
$username = 'ted';
$token = $_POST['token'];
$client = new Client();
$sessionKey = $client->generateSessionKey($token);
$database->save([ // some generic database
    'username' => $username,
    'session' => $sessionKey,
]);
```
Once the user logs in to your app, you may reload the session key.
```
$savedUser = $database->load(['username' => $username]);
if ($savedUser) {
    $client->setAuthSession($savedUser->session);
} else {
    // redirect the user to http://www.last.fm/api/auth/?api_key=xxx
}
```
If a response ever comes back with an invalid session key error, clear the session key from your database.

## Todo:
- More storage adapters and a method of choosing which to use.