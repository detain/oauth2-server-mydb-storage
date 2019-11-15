# MyDb\Generic Storage for thephpleague oauth2 server
[![Travis branch](https://img.shields.io/travis/detain/oauth2-server-mydb-storage/master.svg?style=flat-square)](https://travis-ci.org/detain/oauth2-server-mydb-storage) [![Codecov](https://img.shields.io/codecov/c/github/detain/oauth2-server-mydb-storage.svg?style=flat-square)](https://codecov.io/github/detain/oauth2-server-mydb-storage?branch=master) [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

This is an Implentation of the [thephpleague/oauth2-server](https://github.com/thephpleague/oauth2-server/) 
storage interfaces for MyDb\Generic Storage.

## Usage

```php
$db = new MyDb\Generic('sqlite:oauth2.db');

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
## Installation

The recommended installation method is via [Composer](https://getcomposer.org/).

In your project root just run:

```bash
$ composer require detain/oauth2-server-mydb-storage
```
