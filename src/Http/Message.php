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
	 * @var Headers|null
	 */
	protected ?Headers $headers = null;

	/**
	 *
	 * @var StreamInterface|null
	 */
	protected ?StreamInterface $body;

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

	/**
	 * @return array
	 */
    public function getHeaders(): array
	{
        return $this->headers ? $this->headers->jsonSerialize() : [];
    }

	/**
	 * @param string $name
	 * @return bool
	 */
    public function hasHeader($name): bool
	{
        return isset($this->headers[$name]);
    }

	/**
	 * @param string $name
	 * @return array|null
	 */
    public function getHeader($name): ?array
	{
        return $this->headers[$name] ?? null;
    }

	/**
	 * @param string $name
	 * @return string
	 */
    public function getHeaderLine($name): string
	{
        return implode(', ', $this->headers[$name] ?? []);
    }

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return static
	 */
    public function withHeader($name, $value): self
    {
		$clone = clone $this;
        unset($clone->headers[$name]);
		$clone->headers[$name] = $value;
		return $clone;
    }

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return static
	 */
    public function withAddedHeader($name, $value): self
    {
		$clone = clone $this;
		$clone->headers[$name] = $value;
		return $clone;
    }

	/**
	 * @param string $name
	 * @return static
	 */
    public function withoutHeader($name): self
    {
		$clone = clone $this;
		unset($clone->headers[$name]);
		return $clone;
    }

	/**
	 * @return StreamInterface|null
	 */
    public function getBody(): ?StreamInterface
	{
        return $this->body;
    }

	/**
	 * @param StreamInterface $body
	 * @return static
	 */
    public function withBody(StreamInterface $body): self
    {
		$clone = clone $this;
		$clone->body = $body;
		return $this;
    }

}