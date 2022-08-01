<?php

namespace Polaris\Http\Factory;

use Polaris\Http\UploadedFile;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 *
 */
class UploadedFileFactory implements UploadedFileFactoryInterface
{

	/**
	 * @param StreamInterface $stream
	 * @param int|null $size
	 * @param int $error
	 * @param string|null $clientFilename
	 * @param string|null $clientMediaType
	 * @return UploadedFileInterface
	 */
	public function createUploadedFile(
		StreamInterface $stream,
		int $size = null,
		int $error = \UPLOAD_ERR_OK,
		string $clientFilename = null,
		string $clientMediaType = null): UploadedFileInterface
	{
		return new UploadedFile(
			$stream->getMetadata('uri'),
			$clientFilename,
			$clientMediaType,
			$size,
			$error
		);
	}

	/**
	 * Create a normalized tree of UploadedFile instances from the Environment.
	 *
	 * @param array $globals The global server variables.
	 *
	 * @return array|null A normalized tree of UploadedFile instances or null if none are provided.
	 */
	public static function createFromGlobals(array $globals): ?array
	{
		return static::parseUploadedFiles($globals);
	}

	/**
	 * Parse a non-normalized, i.e. $_FILES superglobal, tree of uploaded file data.
	 *
	 * @param array $uploadedFiles The non-normalized tree of uploaded file data.
	 *
	 * @return array A normalized tree of UploadedFile instances.
	 */
	public static function parseUploadedFiles(array $uploadedFiles): array
	{
		$parsed = [];
		foreach ($uploadedFiles as $field => $uploadedFile) {
			if (!isset($uploadedFile['error'])) {
				if (is_array($uploadedFile)) {
					$parsed[$field] = static::parseUploadedFiles($uploadedFile);
				}
				continue;
			}

			$parsed[$field] = [];
			if (!is_array($uploadedFile['error'])) {
				$parsed[$field] = new UploadedFile(
					$uploadedFile['tmp_name'],
					$uploadedFile['name'] ?? null,
					$uploadedFile['type'] ?? null,
					$uploadedFile['size'] ?? null,
					$uploadedFile['error'],
					true
				);
			} else {
				$subArray = [];
				foreach ($uploadedFile['error'] as $fileIdx => $error) {
					// normalise subarray and re-parse to move the input's keyname up a level
					$subArray[$fileIdx]['name'] = $uploadedFile['name'][$fileIdx];
					$subArray[$fileIdx]['type'] = $uploadedFile['type'][$fileIdx];
					$subArray[$fileIdx]['tmp_name'] = $uploadedFile['tmp_name'][$fileIdx];
					$subArray[$fileIdx]['error'] = $uploadedFile['error'][$fileIdx];
					$subArray[$fileIdx]['size'] = $uploadedFile['size'][$fileIdx];

					$parsed[$field] = static::parseUploadedFiles($subArray);
				}
			}
		}

		return $parsed;
	}

}