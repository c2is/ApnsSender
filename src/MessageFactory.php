<?php

namespace C2iS\ApnsSender;

use C2iS\ApnsSender\Model\Message;
use C2iS\ApnsSender\Model\MessageError;

/**
 * Class MessageFactory
 *
 * @package C2iS\ApnsSender
 */
class MessageFactory
{
    /** @var array */
    protected static $errorMessages = array(
        '0'   => 'No error',
        '1'   => 'Processing error',
        '2'   => 'Missing device token',
        '3'   => 'Missing topic',
        '4'   => 'Missing payload',
        '5'   => 'Invalid token size',
        '6'   => 'Invalid topic size',
        '7'   => 'Invalid payload size',
        '8'   => 'Invalid token',
        '255' => 'Unknown error',
    );

    /**
     * @param $key
     * @param $token
     * @param $content
     *
     * @return \C2iS\ApnsSender\Model\Message
     */
    public static function createMessage($key, $token, $content)
    {
        $message = new Message();
        $message->setExpiry($expiry = time() + (90 * 24 * 60 * 60));

        $payload = pack("C", 1).pack("N", $key).pack("N", $expiry).pack("n", 32);
        $payload .= pack('H*', str_replace(' ', '', $token)).pack("n", strlen($content)).$content;

        $message->setPayload($payload);
        $message->setToken($token);

        return $message;
    }

    /**
     * @param array  $queue
     * @param array  $streamError
     * @param int    $key
     * @param string $token
     *
     * @return \C2iS\ApnsSender\Model\MessageError
     */
    public static function createError(array $queue, $streamError, $key = null, $token = null)
    {
        $errorCode    = null;
        $errorMessage = null;

        if ($streamError) {
            $key          = $streamError['identifier'];
            $token        = $queue[$key];
            $errorCode    = $streamError['status'];
            $errorMessage = self::$errorMessages[$errorCode];
        }

        $error = new MessageError($key, $token, $errorCode, $errorMessage);

        return $error;
    }
}
