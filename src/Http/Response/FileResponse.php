<?php
namespace Polaris\Http\Response;

use Polaris\Http\Exceptions\HttpException;
use Polaris\Http\Response;

/**
 * Class FileResponse
 * @package Polaris\Http\Response
 */
class FileResponse extends Response
{

	/**
	 * @var array
	 */
	protected $types = [
		'css'	=>	'text/css',
		'js'	=>	'application/javascript'
	];

    /**
     * PlainResponse constructor.
     * @param string $file
     * @param int $status
     * @param null $headers
     */
    public function __construct($file, $status = 200, $headers = null)
    {
		if (!file_exists($file)) {
			throw new HttpException(404);
		}
        parent::__construct($status, $headers, $file);
        $suffix = trim(strrchr($file, '.'), '.');
		if (isset($this->types[$suffix])) {
			$this->headers->set('Content-Type', $this->types[$suffix]);
		} else if (function_exists('finfo_file')) {
			$this->headers->set('Content-Type', finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file));
		} else if (function_exists('mime_content_type')) {
			$this->headers->set('Content-Type', mime_content_type($file));
		}
    }

}