<?php

namespace C2iS\ApnsSender;

use C2iS\ApnsSender\Model\Message;
use C2iS\ApnsSender\Utils\Sleep;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class StreamHandler
 *
 * @package C2iS\ApnsSender
 */
class StreamHandler
{
    /** Number of times a notification should be attempted in case a recoverable error happens */
    const RETRY = 5;

    /** Interval between two notifications */
    const RETRY_INTERVAL = 10;

    /** @var \C2iS\ApnsSender\Stream */
    protected $stream;

    /** @var \Psr\Log\LoggerInterface|\Psr\Log\NullLogger */
    protected $logger;

    /**
     * @param \C2iS\ApnsSender\Stream  $stream
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(Stream $stream, LoggerInterface $logger = null)
    {
        $this->stream = $stream;
        $this->logger = null !== $logger ? $logger : new NullLogger();
    }

    /**
     * @param Message $message
     *
     * @return bool
     */
    public function write(Message $message)
    {
        $attempt       = 1;
        $content       = $message->getPayload();
        $contentLength = strlen($content);

        try {
            while (((int)$this->stream->write($content)) !== $contentLength && $attempt++ < self::RETRY) {
                Sleep::millisecond(self::RETRY_INTERVAL);
            }

            $success = $attempt < self::RETRY;
        } catch (\Exception $e) {
            $this->logger->warning(
                'An error occurred writing to APNS stream',
                array(
                    'error_message' => $e->getMessage(),
                )
            );
            $success = false;
        }

        return $success;
    }

    /**
     * @return array|bool
     */
    public function readError()
    {
        $readError = $this->stream->read(6);
        $error     = false;
        $this->stream->close();

        if ($readError && 6 == strlen($readError)) {
            $error = unpack('Ccommand/Cstatus/Nidentifier', $readError);
        }

        return $error;
    }
}
