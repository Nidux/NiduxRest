<?php

namespace Nidux\Rest\Request;

use CURLFile;
use JsonException;
use Nidux\Rest\Exception as Exception;
use Nidux\Rest\Request as Request;
use Traversable;

class Body
{
    /**
     * Prepares a file for upload. To be used inside the parameter declaration for a request.
     * @param string $filename The file path
     * @param string $mimetype MIME type
     * @param string $postname the file name
     * @return string|CURLFile
     * @throws Exception
     */
    public static function prepareFile(string $filename, string $mimetype = '', string $postname = ''): CURLFile|string
    {
        if (!file_exists($filename)) {
            throw new Exception("File not found: $filename");
        }
        return new CURLFile($filename, $mimetype, $postname);
    }

    /**
     * @param $data
     * @return false|string
     * @throws JsonException
     */
    public static function prepareJson($data): bool|string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
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
     * @param array|bool $files
     * @return array
     */
    public static function prepareMultiPart($data, array|bool|object $files = false): array
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
     * @param string $filename
     * @param string $mimetype
     * @param string $postname
     * @return string|CURLFile
     * @throws Exception
     * @deprecated Use prepareFile instead
     */
    public static function File(string $filename, string $mimetype = '', string $postname = ''): string|CURLFile
    {
        return self::prepareFile($filename, $mimetype = '', $postname = '');
    }

    /**
     * @param $data
     * @return false|string
     * @throws JsonException
     * @deprecated Use prepareJson instead
     */
    public static function Json($data): bool|string
    {
        return self::prepareJson($data);
    }

    /**
     * @param $data
     * @return mixed
     * @deprecated Use prepareForm instead
     */
    public static function Form($data): mixed
    {
        return self::prepareForm($data);
    }

    /**
     * @param $data
     * @param $files
     * @return array
     * @deprecated Use prepareMultipart instead
     */
    protected static function MultiPart($data, $files = false): array
    {
        return self::prepareMultiPart($data, $files);
    }
}
