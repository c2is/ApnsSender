<?php

namespace C2iS\ApnsSender;

use C2iS\ApnsSender\Exception\ConnectionFailedException;
use C2iS\ApnsSender\Model\MessageError;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Sender
 */
class Sender
{
    /** Number of times a token should be resent if the error is recoverable */
    const MAX_RETRY = 1;

    /** @var array */
    protected static $shouldRetryErrors = array('1', '255');

    /** @var bool */
    protected $production;

    /** @var string */
    protected $certFile;

    /** @var string */
    protected $certPass;

    /** @var \Psr\Log\LoggerInterface $logger */
    protected $logger;

    /**
     * @param bool                     $production
     * @param string                   $certFile
     * @param string                   $certPass
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        $production = false,
        $certFile = null,
        $certPass = null,
        LoggerInterface $logger = null
    ) {
        $this->production = $production;
        $this->certFile   = $certFile;
        $this->certPass   = $certPass;
        $this->logger     = null !== $logger ? $logger : new NullLogger();
    }

    /**
     * @return \C2iS\ApnsSender\Stream
     */
    protected function createStream()
    {
        return new Stream($this->certFile, $this->certPass, $this->production, $this->logger);
    }

    /**
     * @return \C2iS\ApnsSender\StreamHandler
     */
    protected function createStreamHandler()
    {
        return new StreamHandler($this->createStream(), $this->logger);
    }

    /**
     * @return \C2iS\ApnsSender\Worker
     */
    protected function createWorker()
    {
        return new Worker($this->createStreamHandler(), $this->logger);
    }

    /**
     * Sends the notification to the tokens passed in argument.
     * Returns an array of MessageError objects containing information about each token for which an error occurred
     * and not be recovered from
     *
     * @param string $message
     * @param array  $tokens
     *
     * @return array
     */
    public function send($message, array $tokens)
    {
        $queue   = new \ArrayIterator($tokens);
        $worker  = $this->createWorker();
        $errors  = array();
        $retries = array();

        $payload        = array();
        $payload['aps'] = array('alert' => $message, 'sound' => 'default');
        $content        = json_encode($payload);

        while ($queue->valid()) {
            try {
                $result = $worker->process($content, $queue);
            } catch (ConnectionFailedException $e) {
                $this->logger->info(
                    'Connection failed, aborting',
                    array(
                        'current_key'   => $queue->key(),
                        'current_token' => $queue->current(),
                    )
                );

                return $errors;
            }

            if (true !== $result) {
                $this->manageError($queue, $result, $errors, $retries);
            }
        }

        return $errors;
    }

    /**
     * @param \ArrayIterator                      $queue
     * @param \C2iS\ApnsSender\Model\MessageError $result
     * @param array                               $errors
     * @param array                               $retries
     */
    protected function manageError(\ArrayIterator $queue, MessageError $result, array &$errors, array &$retries)
    {
        $errors[]   = $result;
        $identifier = $result->getCustomIdentifier();
        $queue->seek($identifier);

        if (in_array(
                $result->getErrorCode(),
                self::$shouldRetryErrors
            ) && (!isset($retries[$identifier]) || $retries[$identifier] < self::MAX_RETRY)
        ) {
            $retries[$identifier] = isset($retries[$identifier]) ? $retries[$identifier] + 1 : 1;
            $this->logger->info(
                'Attempting to send token error (recoverable error)',
                array(
                    'key'        => $queue->key(),
                    'token'      => $queue->current(),
                    'error_code' => $result->getErrorCode(),
                )
            );
        } else {
            $queue->next();
        }
    }
}
