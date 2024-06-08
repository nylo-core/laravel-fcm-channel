# Laravel FCM Channel

![Laravel FCM Channel](laravel_fcm.png)

Manage FCM notifications with ease using Laravel FCM Channel.

## Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Changelog](#changelog)
- [Security](#security)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

This package has been built to make sending FCM messages easier. 

There is also a Flutter [package](https://pub.dev/packages/laravel_notify_fcm) you can use to save time for mobile development.

**Overview:**
* Add multiple (FCM) devices to a model in Laravel
* API Routes for adding new devices to a model
* Send FCM notifications using the new "`fcm_channel`" in your Laravel Notification
* Flutter mobile package to help speed up your development with notifications

## Installation

First, install the package via composer:

``` bash
composer require nylo/laravel-fcm-channel
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

Here are the tables it will migrate:
* fcm_devices

Add your Google Service Account to `config/firebase_service_account_json`.

```php
<?php

return '{
    "type": "service_account",
    "project_id": "123456789-me908",
    "private_key_id": "123456789",
    "private_key": "-----BEGIN PRIVATE KEY-----\123456789\n-----END PRIVATE KEY-----\n",
    "client_email": "firebase-adminsdk-9p9z7@123456789-me908.iam.gserviceaccount.com",
    "client_id": "123456789",
    "auth_uri": "https://accounts.google.com/o/oauth2/auth",
    "token_uri": "https://oauth2.googleapis.com/token",
    "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
    "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-9p9z7%123456789-me908.iam.gserviceaccount.com",
    "universe_domain": "googleapis.com"
  }';
```

You can download your Google Service Account in your Firebase Project Settings > Service Accounts > Manage service account permissions > "Actions (three dots) - Manage keys" > Add Key > Create New Key.

Then, paste the JSON into the `firebase_service_account_json` file like in the above example.
> **Note:** It's best to keep the key values in a `.env` file. Don't commit the JSON file to your repository.

You can configure this package in `config/laravelfcm.php`.

## Configuring your Model

Add the `HasFcmDevices` trait to your User Model.
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Nylo\LaravelFCM\Traits\HasFcmDevices; // Use HasFcmDevices trait
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasFcmDevices; // Add it to your model
    
    ...
}
```

This package uses [`laravel/sanctum`](https://laravel.com/docs/sanctum) as the default middleware for your model. 

However, if you want authenticate using a different middleware, you can update the `middleware` key in your **config/laravelfcm.php** file.

## API Endpoints

This package adds API endpoints to your router to allow your application to store devices.

Postman collection [example](laravel_fcm_channel.postman_collection.json)

---

<ins>Store a device</ins>

The default endpoint: `/api/fcm/device`

Method: **PUT**

Authorization: "Bearer {{Sanctum Token}}"

Add this header key: `X-DMeta`

Value: 
```
{
    "uuid": "12992", // required, a uuid which should be from the device.
    "model": "iPhone", // optional
    "version":" 12", // optional
    "display_name": "Tim's iPhone", // optional
    "platform": "IOS" // optional
}
```

Payload body:
```
{
    "is_active": 1, // optional, use this key to define if a device is active or not
    "fcm_token": "kjnsdmnsdc0sdco23" // optional, when you have an FCM token for the device, use this key in the payload
}
```

This will add a new FCM device for a User.
If you provide a `fcm_token` in the payload then the user will be able to receive push notifications.

## Usage

### Sending Notifications

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

        return (new FcmMessage)
            ->title('Parcel Dispatched')
            ->body('Your parcel has been dispatched');

        // or like this

        return [
            'title' => config('app.name'), // Title of the notification
            'body' => $title, // Body of the notification
        ];
    }
```

### Send a Notification

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

    use HasFcmDevices;

    /**
     * Determines if the devices can be notified.
     *
     * @return bool
     */
    public function canSendNotification($notification) : bool
    {
        // $notification - Will return the type of Notification you are trying to send.
        // E.g. $user->notify(new NewsLetterNotification($order));
        // $notification = 'App\Notifications\NewsLetterNotification';
        //
        // The canSendNotification method will be called before dispatching the fcm notification. 
        // If you return True, it will send. If you return False, it will not send.

        if ($notification  == 'App\Notifications\NewsLetterNotification') {
            return ($this->receives_news_letters == true); // example condition
        }
    	return true;
    }
}
```
> By default, the `canSendNotification` method will return `true`.

## Notification Object

Here are the attributes you can assign to a `FcmMessage`.

```php
$notification = new FcmMessage();

$notification->title('My App'); // Title of the notification
$notification->body('Hello, World!'); // Body of the notification
$notification->image('https://example.com/image.jpg'); // Image URL
$notification->badge(1); // Badge number
$notification->sound('custom_sound'); // Sound to play (by default it will play the 'default' sound)
$notification->data(['key' => 'value']); // Custom data
$notification->withoutDefaultSound(); // Disable the default sound
$notification->priorityHighest(); // Set the priority to 'high'
$notification->priorityLowest(); // Set the priority to 'low'
```

## Relationships

When your model is using the `HasFcmDevices` trait you can call the following methods.

```php
<?php

$user = User::first();

$user->fcmDevices;// Returns all the FCM Devices that the user owns

// send notification
$fcmDevice = $user->fcmDevices->first(); 
$message = (new FcmMessage)
                    ->title('My App')
                    ->body('Hello, World!');
$fcmDevice->sendFcmMessage($message);

// or like this

$fcmDevice->sendFcmMessage([
    'title' => 'My App',
    'body' => 'Hello, World!'
]);
```

## Flutter Plugin

Need to send notifications to a Flutter application?

Check out the official repository for that project [here](https://github.com/nylo-core/laravel-notify-fcm).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Security

If you discover any security-related issues, please email hello@Nylo.com instead of using the issue tracker.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Anthony Gordon](https://twitter.com/anthonygordn)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please view our [License File](LICENSE.md) for more information.
