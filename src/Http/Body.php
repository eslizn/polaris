<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim-Http
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim-Http/blob/master/LICENSE (MIT License)
 */
namespace Polaris\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Body
 *
 * This class represents an HTTP message body and encapsulates a
 * streamable resource according to the PSR-7 standard.
 *
 * @link https://github.com/php-fig/http-message/blob/master/src/StreamInterface.php
 */
class Body implements StreamInterface
{

	/**
	 * The underlying stream resource
	 *
	 * @var string
	 */
	protected $data;

	/**
	 * @var integer
	 */
	protected $position;

	/**
	 * Stream metadata
	 *
	 * @var array
	 */
	protected $meta;

	/**
	 * @var boolean
	 */
	protected $attached;

	/**
	 * Stream constructor.
	 * @param mixed $data
	 */
	public function __construct($data)
	{
		$this->attach($data);
	}

	/**
	 * Get stream metadata as an associative array or retrieve a specific key.
	 *
	 * The keys returned are identical to the keys returned from PHP's
	 * stream_get_meta_data() function.
	 *
	 * @link http://php.net/manual/en/function.stream-get-meta-data.php
	 *
	 * @param string $key Specific metadata to retrieve.
	 *
	 * @return array|mixed|null Returns an associative array if no key is
	 *     provided. Returns a specific key value if a key is provided and the
	 *     value is found, or null if the key is not found.
	 */
	public function getMetadata($key = null)
	{
		return isset($this->meta[$key]) ? $this->meta[$key] : null;
	}

	/**
	 * Is a resource attached to this stream?
	 *
	 * Note: This method is not part of the PSR-7 standard.
	 *
	 * @return bool
	 */
	protected function isAttached()
	{
		return $this->attached;
	}

	/**
	 * @param string $data
	 */
	protected function attach($data)
	{
		if (is_resource($data)) {
			debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		}
		if (is_null($data)) {
			$data = '';
		}

		if (is_scalar($data) === false) {
			throw new InvalidArgumentException(__METHOD__ . ' argument must be a valid PHP scalar');
		}

		if ($this->isAttached() === true) {
			$this->detach();
		}

		$this->data = strval($data);
		$this->attached = true;
	}

	/**
	 * Separates any underlying resources from the stream.
	 *
	 * After the stream has been detached, the stream is in an unusable state.
	 *
	 * @return resource|null Underlying PHP stream, if any
	 */
	public function detach()
	{
		$this->data = null;
		$this->meta = [];
		$this->attached = false;
		$this->position = 0;
		return null;
	}

	/**
	 * Reads all data from the stream into a string, from the beginning to end.
	 *
	 * This method MUST attempt to seek to the beginning of the stream before
	 * reading data and read the stream until the end is reached.
	 *
	 * Warning: This could attempt to load a large amount of data into memory.
	 *
	 * This method MUST NOT raise an exception in order to conform with PHP's
	 * string casting operations.
	 *
	 * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
	 * @return string
	 */
	public function __toString()
	{
		return $this->getContents();
	}

	/**
	 * Closes the stream and any underlying resources.
	 */
	public function close()
	{
		$this->detach();
	}

	/**
	 * Get the size of the stream if known.
	 *
	 * @return int|null Returns the size in bytes if known, or null if unknown.
	 */
	public function getSize()
	{
		return strlen($this->data);
	}

	/**
	 * Returns the current position of the file read/write pointer
	 *
	 * @return int Position of the file pointer
	 *
	 * @throws RuntimeException on error.
	 */
	public function tell()
	{
		if (!$this->isAttached()) {
			throw new RuntimeException('Could not get the position of the pointer in stream');
		}

		return $this->position;
	}

	/**
	 * Returns true if the stream is at the end of the stream.
	 *
	 * @return bool
	 */
	public function eof()
	{
		return $this->position >= strlen($this->data);
	}

	/**
	 * Returns whether or not the stream is readable.
	 *
	 * @return bool
	 */
	public function isReadable()
	{
		return true;
	}

	/**
	 * Returns whether or not the stream is writable.
	 *
	 * @return bool
	 */
	public function isWritable()
	{
		return true;
	}

	/**
	 * Returns whether or not the stream is seekable.
	 *
	 * @return bool
	 */
	public function isSeekable()
	{
		return true;
	}

	/**
	 * Seek to a position in the stream.
	 *
	 * @link http://www.php.net/manual/en/function.fseek.php
	 *
	 * @param int $offset Stream offset
	 * @param int $whence Specifies how the cursor position will be calculated
	 *     based on the seek offset. Valid values are identical to the built-in
	 *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
	 *     offset bytes SEEK_CUR: Set position to current location plus offset
	 *     SEEK_END: Set position to end-of-stream plus offset.
	 *
	 * @throws RuntimeException on failure.
	 */
	public function seek($offset, $whence = SEEK_SET)
	{
		switch ($whence) {
			case SEEK_SET:
				$this->position = $offset;
			case SEEK_CUR:
				$this->position += $offset;
			case SEEK_END:
				$this->position = $this->getSize() - 1 - $offset;
			default:
				throw new RuntimeException('Could not seek in stream');
		}
	}

	/**
	 * Seek to the beginning of the stream.
	 *
	 * If the stream is not seekable, this method will raise an exception;
	 * otherwise, it will perform a seek(0).
	 *
	 * @see seek()
	 *
	 * @link http://www.php.net/manual/en/function.fseek.php
	 *
	 * @throws RuntimeException on failure.
	 */
	public function rewind()
	{
		$this->position = 0;
	}

	/**
	 * Read data from the stream.
	 *
	 * @param int $length Read up to $length bytes from the object and return
	 *     them. Fewer than $length bytes may be returned if underlying stream
	 *     call returns fewer bytes.
	 *
	 * @return string Returns the data read from the stream, or an empty string
	 *     if no bytes are available.
	 *
	 * @throws RuntimeException if an error occurs.
	 */
	public function read($length)
	{
		if (!$this->isReadable() || $this->position + $length >= $this->getSize()) {
			throw new RuntimeException('Could not read from stream');
		}
		$data = substr($this->data, $this->position, $length);
		$this->position += $length;
		return $data;
	}

	/**
	 * Write data to the stream.
	 *
	 * @param string $string The string that is to be written.
	 *
	 * @return int Returns the number of bytes written to the stream.
	 *
	 * @throws RuntimeException on failure.
	 */
	public function write($string)
	{
		if (!$this->isWritable() || $this->position >= $this->getSize()) {
			throw new RuntimeException('Could not write to stream');
		}
		$this->data = substr($this->data, 0, $this->position) . $string . substr($this->data, $this->position);
		$this->position += strlen($string);
		return strlen($string);
	}

	/**
	 * Returns the remaining contents in a string
	 *
	 * @return string
	 *
	 * @throws RuntimeException if unable to read or an error occurs while
	 *     reading.
	 */
	public function getContents()
	{
		if (!$this->isReadable()) {
			throw new RuntimeException('Could not get contents of stream');
		}
		return $this->data;
	}

	/**
	 * Returns whether or not the stream is a pipe.
	 *
	 * @return bool
	 */
	public function isPipe()
	{
		return false;
	}

}
