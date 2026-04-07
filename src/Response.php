<?php

namespace Nidux\Rest;

class Response
{
    private int $code;
    private string $raw_body;
    private mixed $body;
    private array $headers;


    /**
     * Constructor for the Response object
     * @param int $code response code of the cURL request
     * @param string $raw_body the raw body of the cURL response
     * @param string $headers raw header string from cURL response
     */
    public function __construct(int $code, string $raw_body, string $headers)
    {
        $this->code = $code;
        $this->headers = $this->parseHeaders($headers);
        $this->raw_body = $raw_body;
        $this->body = $raw_body;
        if (!empty($raw_body)) {
            $json = json_decode($raw_body, false, 512, 0);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->body = $json;
            }
        }
    }

    /**
     * Checks if the request was successful based on HTTP CODES (200-299)
     */
    public function isSuccessful(): bool
    {
        return $this->code >= 200 && $this->code < 300;
    }

    /**
     * Checks if there was a client error (400-499)
     */
    public function isClientError(): bool
    {
        return $this->code >= 400 && $this->code < 500;
    }

    /**
     * Checks if there was a server error (500+)
     */
    public function isServerError(): bool
    {
        return $this->code >= 500;
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

    /**
     * Gets the HTTP response code as an integer
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }


    /**
     * Gets the raw body of the response as a string
     * @return string
     */
    public function getRawBody(): string
    {
        return $this->raw_body;
    }


    /**
     * Gets the body of the response as a string or object
     * @return mixed
     */
    public function getBody(): mixed
    {
        return $this->body;
    }


    /**
     * Gets a decoded associative array from the response body
     * @return array
     */
    public function getArray(): array
    {
        if (!empty($this->raw_body)) {
            $json = json_decode($this->raw_body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }
        return (array)$this->body;
    }

    /**
     * Gets the raw headers of the response as an associative array
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

}
