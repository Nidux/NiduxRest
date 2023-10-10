<?php

namespace Niduxrest;

class Response
{
    private int $code;
    private string $raw_body;
    private mixed $body;
    private array $headers;

    /**
     * @param int $code response code of the cURL request
     * @param string $raw_body the raw body of the cURL response
     * @param string $headers raw header string from cURL response
     * @param array $overrideJsonOpts arguments to pass to json_decode function
     */
    public function __construct(int $code, string $raw_body, string $headers, array $overrideJsonOpts = [])
    {
        $this->code = $code;
        $this->headers = $this->parseHeaders($headers);
        $this->raw_body = $raw_body;
        $this->body = $raw_body;

        //Take the static var just to honor any global definitions
        $overrideJsonOpts = (empty($overrideJsonOpts)) ? Request::getJsonOpts() : [];
        // make sure raw_body is the first argument
        array_unshift($overrideJsonOpts, $raw_body);

        if (function_exists('json_decode')) {
            $json = call_user_func_array('json_decode', $overrideJsonOpts);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->body = $json;
            }
        }
    }

    /**
     * if PECL_HTTP is not available use a fall back function
     *
     * thanks to ricardovermeltfoort@gmail.com
     * http://php.net/manual/en/function.http-parse-headers.php#112986
     *
     * @param string $raw_headers raw headers
     *
     * @return array
     */
    private function parseHeaders(string $raw_headers): array
    {
        if (function_exists('http_parse_headers')) {
            return http_parse_headers($raw_headers);
        } else {
            $key = '';
            $headers = [];

            foreach (explode("\n", $raw_headers) as $i => $h) {
                $h = explode(':', $h, 2);

                if (isset($h[1])) {
                    if (!isset($headers[$h[0]])) {
                        $headers[$h[0]] = trim($h[1]);
                    } else if (is_array($headers[$h[0]])) {
                        $headers[$h[0]] = array_merge($headers[$h[0]], [trim($h[1])]);
                    } else {
                        $headers[$h[0]] = array_merge([$headers[$h[0]]], [trim($h[1])]);
                    }

                    $key = $h[0];
                } else {
                    if (str_starts_with($h[0], "\t")) {
                        $headers[$key] .= "\r\n\t" . trim($h[0]);
                    } else if (!$key) {
                        $headers[0] = trim($h[0]);
                    }
                }
            }

            return $headers;
        }
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getRawBody(): string
    {
        return $this->raw_body;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

}
