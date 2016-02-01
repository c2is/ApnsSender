[![SensioLabsInsight](https://insight.sensiolabs.com/projects/64678405-7340-4a44-8979-973dd37d52b5/mini.png)](https://insight.sensiolabs.com/projects/64678405-7340-4a44-8979-973dd37d52b5)

ApnsSender
=========

Installation
------------

Using composer

    composer require c2is/apns-sender

What it does
------------

The use case this component is answering to is the need to send notifications with the same content to multiple device tokens.
It's no good to send different messages to different tokens, though it's something that could be done with relatively few efforts if the need so arises.

The point is mostly to gracefully handle errors.
The way this works is we open a stream to the notification service and send notifications in batch.

Two kinds of error can happen when writing to the stream:

- The notification service wrote an error into the stream
    If it's a retry-able error (mostly that's if it's an unknown error or a "processing error"), we retry sending the same notification to the same token. If it fails again, we move on to the next.
- No error from the notification service
    The stream just failed for some reason. We retry the last token and move on.

Once everything is sent, a last check for errors is done, with the same result as above.
An array containing all errors that could not be resolved is then returned, so you can handle them however you want.

The goal is to abstract stream errors and gather as much information as possible for you to handle them.
This component handles the stream not being writable after an error occurs, it retries all the tokens that could not be sent, and returns a list of the errors it could not handle including status code and message from APNS so you can do whatever you want with them in your application.

Usage
-----

Sending notifications is made relatively easy :

```php
use C2iS\ApnsSender\Sender;

$production = false; // Default value. If false, sends to gateway.sandbox.push.apple.com. If true, sends to gateway.push.apple.com
$certFile = '/my/file.pem'; // Your certificate file
$certPassword = 'MyPassword'; // Your certificate file's password
$message = 'Just testing my awesome notification system works'; // Your notification content
$tokens array(); // Device tokens this notification should be sent to

$sender = new Sender($production, $certFile, $certPassword);
$errors = $sender->send($message, $tokens);

// Here be your error handling logic
```

The send method returns an array of \C2iS\ApnsSender\Model\MessageError objects :

```php
$error->getCustomIdentifier(); // Returns the array key of the token for which the error occurred
$error->getToken(); // Returns the device token
$error->getErrorCode(); // The APNS error status (255 if not available)
$error->getErrorMessage(); // The APNS error message  (Unknown error if not available)
```

FYI, the status code used :

    0   => No error
    1   => Processing error
    2   => Missing device token
    3   => Missing topic
    4   => Missing payload
    5   => Invalid token size
    6   => Invalid topic size
    7   => Invalid payload size
    8   => Invalid token
    255 => Unknown error
