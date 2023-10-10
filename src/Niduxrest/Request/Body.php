<?php

namespace Niduxrest\Request;

use CURLFile;
use Niduxrest\Exception as Exception;
use Niduxrest\Request as Request;
use Traversable;

class Body
{
    /**
     * Prepares a file for upload. To be used inside the parameters declaration for a request.
     * @param string $filename The file path
     * @param string $mimetype MIME type
     * @param string $postname the file name
     * @return string|CURLFile
     */
    public static function prepareFile(string $filename, string $mimetype = '', string $postname = ''): CURLFile|string
    {
        if (class_exists('CURLFile')) {
            return new CURLFile($filename, $mimetype, $postname);
        }

        if (function_exists('curl_file_create')) {
            return curl_file_create($filename, $mimetype, $postname);
        }

        return sprintf('@%s;filename=%s;type=%s', $filename, $postname ?: basename($filename), $mimetype);
    }

    /**
     * @param $data
     * @return false|string
     * @throws Exception
     */
    public static function prepareJson($data): bool|string
    {
        if (!function_exists('json_encode')) {
            throw new Exception('JSON Extension not available');
        }

        return json_encode($data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function prepareForm($data): mixed
    {
        if (is_array($data) || is_object($data) || $data instanceof Traversable) {
            return http_build_query(Request::buildHTTPCurlQuery($data));
        }

        return $data;
    }

    /**
     * @param $data
     * @param $files
     * @return array
     */
    public static function prepareMultiPart($data, $files = false): array
    {
        if (is_object($data)) {
            return get_object_vars($data);
        }

        if (!is_array($data)) {
            return [$data];
        }

        if ($files !== false) {
            foreach ($files as $name => $file) {
                $data[$name] = call_user_func([__CLASS__, 'prepareFile'], $file);
            }
        }

        return $data;
    }

    /**
     * @deprecated Use prepareFile instead
     * @param string $filename
     * @param string $mimetype
     * @param string $postname
     * @return void
     */
    public static function File(string $filename, string $mimetype = '', string $postname = ''): void
    {
        self::prepareFile($filename, $mimetype = '', $postname = '');
    }

    /**
     * @deprecated Use prepareJson instead
     * @param $data
     * @return false|string
     * @throws Exception
     */
    public static function Json($data): bool|string
    {
        self::prepareJson($data);
    }

    /**
     * @deprecated Use prepareForm instead
     * @param $data
     * @return void
     */
    public static function Form($data)
    {
        self::prepareForm($data);
    }

    /**
     * @deprecated Use prepareMultipart instead
     * @param $data
     * @param $files
     * @return array     *
     */
    protected static function MultiPart($data, $files = false): array
    {
        self::prepareMultiPart($data, $files);
    }
}
