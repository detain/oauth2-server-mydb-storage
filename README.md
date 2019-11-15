# MyDb\Generic Storage for thephpleague oauth2 server
[![Travis branch](https://img.shields.io/travis/detain/oauth2-server-mydb-storage/master.svg?style=flat-square)](https://travis-ci.org/detain/oauth2-server-mydb-storage) [![Codecov](https://img.shields.io/codecov/c/github/detain/oauth2-server-mydb-storage.svg?style=flat-square)](https://codecov.io/github/detain/oauth2-server-mydb-storage?branch=master) [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

This is an Implentation of the [thephpleague/oauth2-server](https://github.com/thephpleague/oauth2-server/) 
storage interfaces for MyDb\Generic Storage.

## Usage

```php
use Detain\OAuth2\Server\Storage\MyDb\AccessTokenStorage;
use Detain\OAuth2\Server\Storage\MyDb\ClientStorage;
use Detain\OAuth2\Server\Storage\MyDb\AuthCodeStorage;
use Detain\OAuth2\Server\Storage\MyDb\RefreshTokenStorage;
use Detain\OAuth2\Server\Storage\MyDb\ScopeStorage;
use Detain\OAuth2\Server\Storage\MyDb\SessionsStorage;

$db = new MyDb\Generic();

$sessionStorage = new SessionStorage($db);
$accessTokenStorage = new AccessTokenStorage($db);
$clientStorage = new ClientStorage($db);
$scopeStorage = new ScopeStorage($db);

$server = new ResourceServer(
	$sessionStorage,
	$accessTokenStorage,
	$clientStorage,
	$scopeStorage
);
//…
```

Once you have an instance of `League\OAuth2\Server\AuthorizationServer` you can set the different storages.

```php
$server->setClientStorage(new Detain\OAuth2\Server\Storage\MyDb\ClientStorage($db));
$server->setSessionStorage(new Detain\OAuth2\Server\Storage\MyDb\SessionStorage($db));
$server->setAccessTokenStorage(new Detain\OAuth2\Server\Storage\MyDb\AccessTokenStorage($db));
$server->setRefreshTokenStorage(new Detain\OAuth2\Server\Storage\MyDb\RefreshTokenStorageStorage($db));
$server->setAuthCodeStorage(new Detain\OAuth2\Server\Storage\MyDb\AuthCodeStorage($db));
$server->setScopeStorage(new Detain\OAuth2\Server\Storage\MyDb\ScopeStorage($db));
```

## Installation

The recommended installation method is via [Composer](https://getcomposer.org/).

In your project root just run:

```bash
$ composer require detain/oauth2-server-mydb-storage
```

## Testing the client credentials grant example

Send the following cURL request:

```
curl -X "POST" "https://mynew.interserver.net/oauth/server/client_credentials.php/access_token" \
	-H "Content-Type: application/x-www-form-urlencoded" \
	-H "Accept: 1.0" \
	--data-urlencode "grant_type=client_credentials" \
	--data-urlencode "client_id=myawesomeapp" \
	--data-urlencode "client_secret=abc123" \
	--data-urlencode "scope=basic email"
```

## Testing the password grant example

Send the following cURL request:

```
curl -X "POST" "https://mynew.interserver.net/oauth/server/password.php/access_token" \
	-H "Content-Type: application/x-www-form-urlencoded" \
	-H "Accept: 1.0" \
	--data-urlencode "grant_type=password" \
	--data-urlencode "client_id=myawesomeapp" \
	--data-urlencode "client_secret=abc123" \
	--data-urlencode "username=alex" \
	--data-urlencode "password=whisky" \
	--data-urlencode "scope=basic email"
```

## Testing the refresh token grant example

Send the following cURL request. Replace `{{REFRESH_TOKEN}}` with a refresh token from another grant above:

```
curl -X "POST" "https://mynew.interserver.net/oauth/server/refresh_token.php/access_token" \
	-H "Content-Type: application/x-www-form-urlencoded" \
	-H "Accept: 1.0" \
	--data-urlencode "grant_type=refresh_token" \
	--data-urlencode "client_id=myawesomeapp" \
	--data-urlencode "client_secret=abc123" \
	--data-urlencode "refresh_token={{REFRESH_TOKEN}}"
```

