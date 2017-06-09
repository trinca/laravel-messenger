[![Build Status](https://img.shields.io/travis/trvmsg/laravel-messenger/v2.svg?style=flat-square)](https://travis-ci.org/trvmsg/laravel-messenger)
[![Code Climate](https://img.shields.io/codeclimate/github/trvmsg/laravel-messenger.svg?style=flat-square)](https://codeclimate.com/github/trvmsg/laravel-messenger)
[![Latest Version](https://img.shields.io/github/release/trvmsg/laravel-messenger.svg?style=flat-square)](https://github.com/trvmsg/laravel-messenger/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/trvmsg/messenger.svg?style=flat-square)](https://packagist.org/packages/trvmsg/messenger)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)


## Features
* Multiple conversations per user
* Optionally loop in additional users with each new message
* View the last message for each thread available
* Returns either all messages in the system, all messages associated to the user, or all message associated to the user with new/unread messages
* Return the users unread message count easily
* Very flexible usage so you can implement your own acess control

## Common uses
* Open threads (everyone can see everything)
* Group messaging (only participants can see their threads)
* One to one messaging (private or direct thread)

## Installation (Laravel 5.x)
In composer.json:

    "require": {
        "trinca/trvmsg": "~2.0"
    }

Run:

    composer update

Add the service provider to `config/app.php` under `providers`:

    'providers' => [
        Trvmsg\Messenger\MessengerServiceProvider::class,
    ]

Publish Assets

	php artisan vendor:publish --provider="Trvmsg\Messenger\MessengerServiceProvider"
	
Update config file to reference your User Model:

	config/messenger.php
	
Create a `users` table if you do not have one already. If you need one, simply use [this example](https://github.com/trvmsg/laravel-messenger/blob/v2/src/Trvmsg/Messenger/examples/create_users_table.php) as a starting point, then migrate.

Migrate your database:

    php artisan migrate

Add the trait to your user model:

    use Trvmsg\Messenger\Traits\Messagable;
    
    class User extends Model {
    	use Messagable;
    }


## Examples
* [Controller](https://github.com/trvmsg/laravel-messenger/blob/v2/src/Trvmsg/Messenger/examples/MessagesController.php)
* [Routes](https://github.com/trvmsg/laravel-messenger/blob/v2/src/Trvmsg/Messenger/examples/routes.php)
* [Views](https://github.com/trvmsg/laravel-messenger/tree/v2/src/Trvmsg/Messenger/examples/views)

__Note:__ These examples use the [laravelcollective/html](http://laravelcollective.com/docs/5.0/html) package that is no longer included in Laravel 5 out of the box. Make sure you require this dependency in your `composer.json` file if you intend to use the example files.

## Example Projects
* [WIP] [Pusher](https://github.com/trvmsg/laravel-messenger-pusher-demo)
* [WIP] [Lumen API](https://github.com/trvmsg/lumen-messenger-api)

## Security

If you discover any security related issues, please email [Chris Gmyr](mailto:trvmsg@gmail.com) instead of using the issue tracker.

## Credits

- [Chris Gmyr](https://github.com/trvmsg)
- [All Contributors](../../contributors)

### Special Thanks
This package used [AndreasHeiberg/laravel-messenger](https://github.com/AndreasHeiberg/laravel-messenger) as a starting point.
