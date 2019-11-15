# MyDb\Generic Repository for thephpleague oauth2 server
[![Travis branch](https://img.shields.io/travis/detain/oauth2-server-mydb-storage/master.svg?style=flat-square)](https://travis-ci.org/detain/oauth2-server-mydb-storage) [![Codecov](https://img.shields.io/codecov/c/github/detain/oauth2-server-mydb-storage.svg?style=flat-square)](https://codecov.io/github/detain/oauth2-server-mydb-storage?branch=master) [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

This is an Implentation of the [thephpleague/oauth2-server](https://github.com/thephpleague/oauth2-server/) 
storage interfaces for MyDb\Generic Repository.

## Usage

```php
use Detain\OAuth2\Server\Repository\MyDb\AccessTokenRepository;
use Detain\OAuth2\Server\Repository\MyDb\ClientRepository;
use Detain\OAuth2\Server\Repository\MyDb\ScopeRepository;
use Detain\OAuth2\Server\Repository\MyDb\SessionsRepository;
use Detain\OAuth2\Server\Repository\MyDb\AuthCodeRepository;
use Detain\OAuth2\Server\Repository\MyDb\RefreshTokenRepository;
use League\OAuth2\Server\ResourceServer;
use MyDb\Mysqli\Db;

$db = new Db();

$sessionRepository = new SessionRepository($db);
$accessTokenRepository = new AccessTokenRepository($db);
$clientRepository = new ClientRepository($db);
$scopeRepository = new ScopeRepository($db);

$server = new ResourceServer($sessionRepository, $accessTokenRepository, $clientRepository, $scopeRepository);
```

Once you have an instance of `League\OAuth2\Server\AuthorizationServer` you can set the different storages.

```php
use Detain\OAuth2\Server\Repository\MyDb\AccessTokenRepository;
use Detain\OAuth2\Server\Repository\MyDb\ClientRepository;
use Detain\OAuth2\Server\Repository\MyDb\ScopeRepository;
use Detain\OAuth2\Server\Repository\MyDb\SessionsRepository;
use Detain\OAuth2\Server\Repository\MyDb\AuthCodeRepository;
use Detain\OAuth2\Server\Repository\MyDb\RefreshTokenRepository;

$server->setAccessTokenRepository(new AccessTokenRepository($db));
$server->setClientRepository(new ClientRepository($db));
$server->setScopeRepository(new ScopeRepository($db));
$server->setSessionRepository(new SessionRepository($db));
$server->setAuthCodeRepository(new AuthCodeRepository($db));
$server->setRefreshTokenRepository(new RefreshTokenRepositoryRepository($db));
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
	--data-urlencode "client_id=rocketchat_2773" \
	--data-urlencode "client_secret=s3cr3t" \
	--data-urlencode "scope=basic email"
```

## Testing the password grant example

Send the following cURL request:

```
curl -X "POST" "https://mynew.interserver.net/oauth/server/password.php/access_token" \
	-H "Content-Type: application/x-www-form-urlencoded" \
	-H "Accept: 1.0" \
	--data-urlencode "grant_type=password" \
	--data-urlencode "client_id=rocketchat_2773" \
	--data-urlencode "client_secret=s3cr3t" \
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
	--data-urlencode "client_id=rocketchat_2773" \
	--data-urlencode "client_secret=s3cr3t" \
	--data-urlencode "refresh_token={{REFRESH_TOKEN}}"
```

