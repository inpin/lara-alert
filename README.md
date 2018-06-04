LaraAlert
============
[![Build Status](https://travis-ci.org/inpin/lara-alert.svg?branch=master)](https://travis-ci.org/inpin/lara-alert)
[![StyleCI](https://github.styleci.io/repos/136009976/shield?branch=master)](https://github.styleci.io/repos/136009976)
[![Maintainability](https://api.codeclimate.com/v1/badges/8ac3e0ea0c6324880c8e/maintainability)](https://codeclimate.com/github/inpin/lara-alert/maintainability)
[![Latest Stable Version](https://poser.pugx.org/inpin/lara-alert/v/stable)](https://packagist.org/packages/inpin/lara-alert)
[![Total Downloads](https://poser.pugx.org/inpin/lara-alert/downloads)](https://packagist.org/packages/inpin/lara-alert)
[![Latest Unstable Version](https://poser.pugx.org/inpin/lara-alert/v/unstable)](https://packagist.org/packages/inpin/lara-alert)
[![License](https://poser.pugx.org/inpin/lara-alert/license)](https://packagist.org/packages/inpin/lara-alert)

Trait for Laravel Eloquent models to allow easy implementation of a "user alerts" feature.

#### Composer Install (for Laravel 5.5 and above)

	composer require inpin/lara-alert

#### Install and then run the migrations

```php
'providers' => [
    \Inpin\LaraAlert\LaraAlertServiceProvider::class,
],
```

```bash
php artisan vendor:publish --provider="Inpin\LaraAlert\LaraAlertServiceProvider" --tag=migrations
php artisan migrate
```

#### Model and database schema

it will create you a table of `laraalert_alerts` with following fields:

`type` is the type of alert, ex: 'alert', 'confirmation complete', 'software update, etc.

`description` a nullable text which describe alert.

`seen_at` determines if current alert is new (seen_at is null_) or not (seen_at fills with timestamp)

#### Setup your models

```php
class Book extends \Illuminate\Database\Eloquent\Model {
    use Inpin\LaraAlert\Alertable;
}
```

#### Sample Usage

```php
// Create an alert with type of 'alert' by currently logged in user without description.
$book->createAlert();

// Create a alert on $book object with type of "some-alert-type", and null description.
$book->createAlert('some-alert-type');

// Create a alert on $book object with type of "some-alert-type", null description,
// and current logged in user form 'api' guard as owner.
$book->createAlert('some-alert-type', 'api');

// Create a alert on $book object with type of "some-alert-type", null description, and $user as owner.
$book->createAlert('some-alert-type', $user);

// Create a alert on $book object with type of "some-alert-type", with description of "some message,
// and current logged in user form 'api' guard as owner.
$book->createAlert('some-alert-type', 'api', 'some message');

// Create a alert on $book object with type of "some-alert-type", with description of "some message,
// and current logged in user form 'api' guard as owner.
$book->createAlert('some-alert-type', $user, 'some message');

// Create a alert on $book object with "alert item id" of 1 and 2, put user message of "some message on it",
// and put $user (3rd param) as alerter.
$book->createAlert([1, 2], 'some message', $user');

$book->alerts(); // HasMany relation to alerts of book.
$book->alerts; // Collection of book's alerts.

$book->isAlertedBy() // check if current logged in user form default guard has alerted book.
$book->isAlertedBy('api') // check if current logged in user form 'api' guard has alerted book.
$book->isAlertedBy($user) // check if '$user' has alerted book.

$book->isAlerted() // check if $book has alert.
$book->isAlerted // check if $book has alert.

$book->alertsCount; // return number of alerts on $book.
$book->alertsCount(); // return number of alerts on $book.
```

Alert objects

```php
// set seen_at with current timestamp.
$alert->seen();

// check if $alert is new or not.
$alert->isNew();
$alert->isNew;

// check if $alert is seen or not.
$alert->isSeen();
$alert->isSeen;
```

#### Credits

 - Mohammad Nourinik - http://inpinapp.com
