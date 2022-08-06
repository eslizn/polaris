<?php

namespace Polaris\Http\Response;

use Polaris\Http\Exception\HttpException;
use Polaris\Http\Response;

/**
 *
 */
class FileResponse extends Response
{

    /**
     * @var array
     */
    protected array $types = [
        'css'	=>	'text/css',
        'js'	=>	'application/javascript'
    ];

    /**
     * FileResponse constructor.
     *
     * @param string $path
     * @param int $status
     * @param null $headers
     * @throws HttpException
     */
    public function __construct($path, $status = 200, $headers = null)
    {
        if (!file_exists($path)) {
            throw new HttpException(404);
        }
        parent::__construct($status, $headers, $path);
        $suffix = trim(strrchr($path, '.'), '.');
        if (isset($this->types[$suffix])) {
            $this->headers['Content-Type'] = $this->types[$suffix];
        } else if (function_exists('finfo_file')) {
            $this->headers['Content-Type'] = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
        } else if (function_exists('mime_content_type')) {
            $this->headers['Content-Type'] = mime_content_type($path);
        }
    }

}