<?php

namespace Polaris\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 *
 */
abstract class Message implements MessageInterface
{

    /**
     * @var string
     */
    protected string $version = '1.1';

    /**
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return static
     */
    public function withProtocolVersion($version): self
    {
        $clone = clone $this;
        $clone->version = $version;
        return $clone;
    }

    public function getHeaders()
    {
        // TODO: Implement getHeaders() method.
    }

    public function hasHeader($name)
    {
        // TODO: Implement hasHeader() method.
    }

    public function getHeader($name)
    {
        // TODO: Implement getHeader() method.
    }

    public function getHeaderLine($name)
    {
        // TODO: Implement getHeaderLine() method.
    }

    public function withHeader($name, $value)
    {
        // TODO: Implement withHeader() method.
    }

    public function withAddedHeader($name, $value)
    {
        // TODO: Implement withAddedHeader() method.
    }

    public function withoutHeader($name)
    {
        // TODO: Implement withoutHeader() method.
    }

    public function getBody()
    {
        // TODO: Implement getBody() method.
    }

    public function withBody(StreamInterface $body)
    {
        // TODO: Implement withBody() method.
    }
}