<?php

namespace C2iS\ApnsSender\Model;

/**
 * Class Message
 *
 * @package C2iS\ApnsSender\Model
 */
class Message
{
    /** @var int */
    protected $expiry;

    /** @var string */
    protected $payload;

    /** @var string */
    protected $token;

    /**
     * @return int
     */
    public function getExpiry()
    {
        return $this->expiry;
    }

    /**
     * @param int $expiry
     *
     * @return $this
     */
    public function setExpiry($expiry)
    {
        $this->expiry = $expiry;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param string $payload
     *
     * @return $this
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

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
}
