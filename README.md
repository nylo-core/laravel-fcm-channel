# Laravel FCM Channel

![Laravel FCM Channel](laravel_fcm.png)

Manage FCM notifications with ease using Laravel FCM Channel.

## Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)    
- [Changelog](#changelog)
- [Testing](#testing)
- [Security](#security)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

This package has been built to make sending FCM messages easier. 

There is also a Flutter [package](https://pub.dev/packages/laravel_fcm) you can use to save time for mobile development.

**Overview:**
* Add multiple (FCM) devices to a model in Laravel
* API Routes for adding new devices to a model
* Send FCM notifications using the new "`fcm_channel`" in your Laravel Notification
* Flutter mobile package to help speed up your development with notifications

## Installation

First, install the package via composer:

``` bash
composer require veskodigital/laravel-fcm-channel
```

The package will automatically register itself.

## Configuration

Run the `install` command.

```bash
php artisan laravelfcm:install
```
This will add a (`laravelfcm.php`) config file

ServiceProvider to your app.php: `App\Providers\FcmAppServiceProvider::class`

Then, ask if you want to run the migrations.

Here's the tables it will migrate:
* fcm_user_devices
* fcm_user_devices_api_requests

Add your FCM server token to your `.env` file.

```bash
LARAVEL_FCM_SERVER_KEY="MyFCMServerKey"
```

You can find your Fcm Server Key in your Firebase Project Settings > Cloud Messaging.

You can fully configure this package in the `config/laravelfcm.php` file (this file should be added after you run `php artisan laravelfcm:install`).

## Configuring your Model

Add the `HasFCMDevices` trait to your User Model.
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use VeskoDigital\LaravelFCM\Models\Traits\HasFCMDevices; // Use HasFCMDevices trait
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasFCMDevices; // Add it to your model
    
    ...
}
```

This package uses [`laravel/sanctum`](https://laravel.com/docs/sanctum) as the default middleware for your model. 

However, if you want authenticate using a different middleware, you can update the `config/laravelfcm.php` file key "middleware".

The middleware is used when authenticating the user through the API endpoints this package creates.

## API Endpoints

This package adds API endpoints to your router to allow your application to store devices.

Postman collection [example](laravel_fcm_postman_collection.json)

---

Store a device:

The default endpoint: `/api/fcm/device`

Method: **PUT**

Authorization: "Bearer {{Sanctum Token}}"

Add this header key: `X-DMeta`

Value: 
```
{
    "uuid": "12992", // required, a uuid which should be from the device. The value must be persistented on the device.
    "model": "iphone", // optional
    "version":" 12", // optional
    "display_name": "Maes iPhone", // optional
    "platform": "IOS" // optional
}
```

Payload body:
```
{
    "is_active": 1, // optional, use this key to define if a device active or not
    "push_token": "kjnsdmnsdc0sdco23" // optional, when you have an FCM token for the device, use this key in the payload
}
```

This will add a new FCM device for a User.
If you provide a `push_token` in the payload then the user will be able to receive push notifications.

## Sending Notifications

To send a notification using the FCMChannel, first create a Notification in your Laravel project.
```bash
php artisan make:notification ParcelDispatchedNotification
```

After creating your notification, add a `fcm_channel` to the array below.
```php
    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [
            'mail',
            'fcm_channel', // add this
        ];
    }
```

Then, add the following snippet to your notification class.
```php
    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toFcm($notifiable)
    {
        return [
            'title' => config('app.name'), // Laravel App Name
            'body' => $title, // Body of the notification
            'priority' => 'high', // Priority
        ];
    }
```

### Try sending a notification

```php
$user->notify(new ParcelDispatchedNotification($order));
```
This will send a notification to the user's devices.

## Control if an FCM notification should be sent

In some scenarios, you may only want to notify a user based on a condition.

In your `User` model class, add the following snippet.
```php
<?php
...
class User {

    use HasFCMDevices;

    /**
     * Determines if the devices can be notified.
     *
     * @return bool
     */
    public function canSendNotification($notification) : bool
    {
        // $notification - Will return the type of Notification you are trying to send.
        // E.g. first send a notification which is using the `fcm_channel`
        // $user->notify(new ParcelDispatchedNotification($order));
        //
        // The canSendNotification method will be called before dispatching the fcm notifications and
        // perform a check in this method. If you return True, it will send. If you return False, it will not send.
        //
        // You can check the type of notification that is trying to send from the $notification variable.
        // Using the above example. $notification = 'App\Notifications\ParcelDispatchedNotification'.

        if ($notification  == 'App\Notifications\ParcelDispatchedNotification') {
            return ($this->receives_notificiations == true); // example condition
        }
    	return true;
    }
}
...

class User extends Authenticatable
{   
    ...

    /**
     * Determines if the devices can be notified.
     *
     * @return bool
     */
    public function canSendNotification($notification) : bool
    {
    	return true;
    }
}
```

## Notification Object

Here's the attributes you can assign to a `FCMNotification`.

```php
$notification = new FCMNotification();

$notification->setTitle('My Application');
$notification->setBody('Hello World');
$notification->setAndroidChannelId('1');
$notification->setBadge(1);
$notification->setClickAction('NOTIFICATION_CLICK');
$notification->setSound('default');
$notification->setIcon(''); // android only, set the name of your drawable resource as string
$notification->setTag(''); // Identifier used to replace existing notifications in the notification drawer.
$notification->setContentAvailable(''); // On Apple platforms, use this field to represent content-available in the APNs payload.
```

## Relationships

When your model is using the `HasFCMDevices` trait you can call the following methods.

```php
<?php

$user = User::first();

$user->fcmDevices // Returns all the FCM Devices that the user owns

// send notification
$fcmDevice = $user->fcmDevices->first(); 
$notification = new FCMNotification(...);
$fcmDevice->send($notification);
```

## Flutter Plugin

Need to send notifications to a Flutter application?

Check out the offical repository for that project [here](https://github.com/veskodigital/flutter-laravel-fcm).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Security

If you discover any security related issues, please email hello@veskodigital.com instead of using the issue tracker.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Anthony Gordon](https://twitter.com/anthonygordn)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
