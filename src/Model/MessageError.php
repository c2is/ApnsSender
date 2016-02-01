<?php

namespace C2iS\ApnsSender\Model;

/**
 * Class MessageError
 */
class MessageError
{
    /** @var int */
    protected $customIdentifier;

    /** @var string */
    protected $token;

    /** @var int */
    protected $errorCode;

    /** @var string */
    protected $errorMessage;

    /**
     * @param int    $customIdentifier
     * @param string $token
     * @param int    $errorCode
     * @param string $errorMessage
     */
    public function __construct($customIdentifier, $token, $errorCode = 255, $errorMessage = 'Unknown error')
    {
        $this->customIdentifier = $customIdentifier;
        $this->token            = $token;
        $this->errorCode        = $errorCode;
        $this->errorMessage     = $errorMessage;
    }

    /**
     * @return int
     */
    public function getCustomIdentifier()
    {
        return $this->customIdentifier;
    }

    /**
     * @param int $customIdentifier
     *
     * @return $this
     */
    public function setCustomIdentifier($customIdentifier)
    {
        $this->customIdentifier = $customIdentifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param int $errorCode
     *
     * @return $this
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @param string $errorMessage
     *
     * @return $this
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }
}
