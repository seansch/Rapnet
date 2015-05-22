# Rapnet Price SOAP Wrapper

## Installation

First, pull in the package through Composer.

```js
"require": {
    "seansch/rapnet": "dev-master"
}

```

And then, if using Laravel 5, include the service provider within `app/config/app.php`.

```php
'providers' => [
    'Seansch\Rapnet\RapnetServiceProvider'
];

```

And, for convenience, add a facade alias to this same file at the bottom:

```php
'aliases' => [
    'Rapnet' => 'Seansch\Rapnet\RapnetFacade'
];

```

Publish the config file `app/config/rapnet.php` and edit with your details
```php
php artisan vendor:publish

```

## Usage


```php


```