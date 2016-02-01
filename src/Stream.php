<?php

namespace C2iS\ApnsSender;

use C2iS\ApnsSender\Exception\ConnectionFailedException;
use C2iS\ApnsSender\Utils\Sleep;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Stream
 *
 * @package C2iS\ApnsSender
 */
class Stream
{
    const HOST_PROD = 'gateway.push.apple.com';
    const HOST_SANDBOX = 'gateway.sandbox.push.apple.com';
    const PORT = 2195;

    /** Number of retries for establishing a connection */
    const RETRY = 5;

    /** Interval between connection retry, in milliseconds */
    const RETRY_INTERVAL = 200;

    /** @var null|string */
    protected $certFile;

    /** @var null|string */
    protected $certPass;

    /** @var null|resource */
    protected $apns;

    /** @var null|LoggerInterface */
    protected $logger;

    /** @var bool */
    protected $production = false;

    /**
     * @param string                   $certFile
     * @param string                   $certPass
     * @param bool                     $production
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct($certFile = null, $certPass = null, $production = false, LoggerInterface $logger = null)
    {
        $this->certFile   = $certFile;
        $this->certPass   = $certPass;
        $this->production = $production;
        $this->logger     = null !== $logger ? $logger : new NullLogger();
    }

    /**
     * Returns the open socket to APNS if already established. Otherwise, attempts to initialize the socket.
     * Returns the handle on the socket in case of success or null if the connection could not be established.
     *
     * @return bool|resource
     */
    protected function createApns()
    {
        $apns          = null;
        $error         = $errorString = '';
        $streamContext = $this->createStreamContext();

        try {
            $host = $this->production ? self::HOST_PROD : self::HOST_SANDBOX;
            $apns = stream_socket_client(
                sprintf('ssl://%s:%s', $host, self::PORT),
                $error,
                $errorString,
                2,
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT,
                $streamContext
            );
        } catch (\Exception $e) {
            $errorString = $e->getMessage();
            $apns        = false;
        }

        if (!$apns) {
            $this->logger->critical('Error initializing connection to APNS', array('error_message' => $errorString));
        }

        return $apns;
    }

    /**
     * Returns a new stream_context. Uses a certificate and a certificate password if available.
     *
     * @return resource
     */
    protected function createStreamContext()
    {
        if (null !== $this->certFile && null !== $this->certPass) {
            $streamContext = stream_context_create(
                array(
                    'ssl' => array(
                        'local_cert' => $this->certFile,
                        'passphrase' => $this->certPass,
                    ),
                )
            );
        } else {
            $streamContext = stream_context_create();
        }

        return $streamContext;
    }

    /**
     * Returns the open socket to APNS or throws an exception if the socket is not initialized and the connection fails.
     * If the connection fails, retries up to self::RETRY times.
     *
     * @return resource
     * @throws \C2iS\ApnsSender\Exception\ConnectionFailedException
     */
    public function getApns()
    {
        if (!$this->apns) {
            $attempt = 1;

            while (!($apns = $this->createApns()) && $attempt++ < self::RETRY) {
                Sleep::millisecond(self::RETRY_INTERVAL);
            }

            if ($apns) {
                $this->apns = $apns;
                stream_set_blocking($apns, 0);
            } else {
                throw new ConnectionFailedException(
                    sprintf(
                        'Impossible to initialize connexion APNS after %s attempts.',
                        self::RETRY
                    )
                );
            }
        }

        return $this->apns;
    }

    /**
     * @return boolean
     */
    public function isProduction()
    {
        return $this->production;
    }

    /**
     * @param boolean $production
     *
     * @return $this
     */
    public function setProduction($production)
    {
        $this->production = $production;

        return $this;
    }

    /**
     * @param $message
     *
     * @return int
     * @throws \C2iS\ApnsSender\Exception\ConnectionFailedException
     */
    public function write($message)
    {
        $bytesWritten = fwrite($this->getApns(), $message, strlen($message));

        return $bytesWritten;
    }

    /**
     * @param int $length
     *
     * @return string
     * @throws \C2iS\ApnsSender\Exception\ConnectionFailedException
     */
    public function read($length)
    {
        return fread($this->getApns(), $length);
    }

    /**
     * Force closes the stream.
     */
    public function close()
    {
        fclose($this->apns);
        $this->apns = null;
    }
}
