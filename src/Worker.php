<?php

namespace C2iS\ApnsSender;

use C2iS\ApnsSender\Utils\Sleep;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class Worker
 *
 * @package C2iS\ApnsSender
 */
class Worker
{
    /** Interval between two notification (in milliseconds) */
    const SEND_INTERVAL = 1;

    /** Wait time before a last check for errors after queue is traversed */
    const END_WAIT = 250;

    /** @var \C2iS\ApnsSender\StreamHandler */
    protected $streamHandler;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /**
     * @param \C2iS\ApnsSender\StreamHandler $streamHandler
     * @param \Psr\Log\LoggerInterface       $logger
     */
    public function __construct(StreamHandler $streamHandler, LoggerInterface $logger = null)
    {
        $this->streamHandler = $streamHandler;
        $this->logger        = null !== $logger ? $logger : new NullLogger();
    }

    /**
     * @param string         $content
     * @param \ArrayIterator $queue
     *
     * @return \C2iS\ApnsSender\Model\MessageError|bool
     */
    public function process($content, \ArrayIterator $queue)
    {
        while ($queue->valid()) {
            $key     = $queue->key();
            $token   = $queue->current();
            $message = MessageFactory::createMessage($key, $token, $content);

            if (!$writeResult = $this->streamHandler->write($message)) {
                $streamError  = $this->streamHandler->readError();
                $messageError = MessageFactory::createError($queue->getArrayCopy(), $streamError, $key, $token);
                $this->logger->info(
                    'Error sending notification to token',
                    array(
                        'current_key'   => $key,
                        'current_token' => $token,
                        'error_code'    => $messageError->getErrorCode(),
                        'error_message' => $messageError->getErrorMessage(),
                        'error_key'     => $messageError->getCustomIdentifier(),
                        'error_token'   => $messageError->getToken(),
                    )
                );

                return $messageError;
            }

            $queue->next();
            Sleep::millisecond(self::SEND_INTERVAL);
        }

        Sleep::millisecond(self::END_WAIT);
        $streamError = $this->streamHandler->readError();

        if ($streamError) {
            $messageError = MessageFactory::createError($queue->getArrayCopy(), $streamError);
            $this->logger->info(
                'Error sending notification to token',
                array(
                    'key'           => $messageError->getCustomIdentifier(),
                    'token'         => $messageError->getToken(),
                    'error_code'    => $messageError->getErrorCode(),
                    'error_message' => $messageError->getErrorMessage(),
                )
            );

            return $messageError;
        }

        return true;
    }
}
