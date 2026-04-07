<?php

namespace Nidux\Rest;

use CURLFile;
use JsonException;
use Nidux\Rest\Enum\Method;
use Nidux\Rest\Request\Body;

class Request
{
    private static array $globalHeaders = [
        'User-Agent' => 'niduxrest-php/3.0'
    ];
    private Method $method = Method::GET;
    private string $url = '';
    private array $headers = [];
    private mixed $body = null;
    private int $timeout = 30;
    private bool $verifyTargetPeer = true;
    private int $verifyTargetHost = 2;
    private array $customCurlOptions = [];

    /**
     * Start a new request.
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Set the timeout for the request.
     * @param int $seconds
     * @return $this
     */
    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Enable or disable peer verification on the request.
     * @param bool $enabled
     * @return $this
     */
    public function setPeerVerification(bool $enabled = true): self
    {
        $this->verifyTargetPeer = $enabled;
        return $this;
    }

    /**
     * Enable or disable host verification on the request.
     * @param bool $enabled
     * @return $this
     */
    public function setHostVerification(bool $enabled = true): self
    {
        $this->verifyTargetHost = $enabled ? 2 : 0;
        return $this;
    }

    /**
     * Add a Bearer token to the request.
     * @param string $token
     * @return $this
     */
    public function withBearerToken(string $token): self
    {
        return $this->withHeader('Authorization', "Bearer $token");
    }

    /**
     * Add a custom header to the request.
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function withHeader(string $key, string $value): self
    {
        $this->headers[strtolower(trim($key))] = $value;
        return $this;
    }

    /**
     * Set the HTTP method to POST and target the given URL.
     * @param string $url
     * @return $this
     */
    public function post(string $url): self
    {
        return $this->withMethod(Method::POST)->to($url);
    }

    /**
     * Set the target URL for the request.
     * @param string $url
     * @return $this
     */
    public function to(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Set the HTTP method for the request.
     * @param Method $method
     * @return $this
     */
    public function withMethod(Method $method): self
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Set the HTTP method to PUT and target the given URL.
     * @param string $url
     * @return $this
     */
    public function put(string $url): self
    {
        return $this->withMethod(Method::PUT)->to($url);
    }

    /**
     * Set the HTTP method to PATCH and target the given URL.
     * @param string $url
     * @return $this
     */
    public function patch(string $url): self
    {
        return $this->withMethod(Method::PATCH)->to($url);
    }

    /**
     * Set the HTTP method to DELETE and target the given URL.
     * @param string $url
     * @return $this
     */
    public function delete(string $url): self
    {
        return $this->withMethod(Method::DELETE)->to($url);
    }

    /**
     * Set the HTTP method to GET and target the given URL.
     * @param string $url
     * @return $this
     */
    public function get(string $url): self
    {
        return $this->withMethod(Method::GET)->to($url);
    }

    /**
     * Set the HTTP method to HEAD and target the given URL.
     * @param string $url
     * @return $this
     */
    public function head(string $url): self
    {
        return $this->withMethod(Method::HEAD)->to($url);
    }

    /**
     * Set the HTTP method to OPTIONS and target the given URL.
     * @param string $url
     * @return $this
     */
    public function options(string $url): self
    {
        return $this->withMethod(Method::OPTIONS)->to($url);
    }

    /**
     * Set the HTTP method to TRACE and target the given URL.
     * @param string $url
     * @return $this
     */
    public function trace(string $url): self
    {
        return $this->withMethod(Method::TRACE)->to($url);
    }


    /**
     * Sets a multipart body, useful for file uploads.
     * @param array $data
     * @return $this
     */
    public function withMultipartBody(array $data): self
    {
        $this->body = Body::prepareMultiPart($data);
        return $this;
    }

    /**
     * Appends query parameters to the current URL.
     * @param array $params
     * @return $this
     */
    public function withQuery(array $params): self
    {
        $queryString = http_build_query(self::buildHTTPCurlQuery($params));
        $this->url .= (str_contains($this->url, '?') ? '&' : '?') . $queryString;

        return $this;
    }

    /**
     * This function is useful for serializing multidimensional arrays, and avoid getting
     * the 'Array to string conversion' notice
     *
     * @param object|array $data array to flatten.
     * @param bool|string $parent parent key or false if no parent
     *
     * @return array
     */
    public static function buildHTTPCurlQuery(object|array $data, bool|string $parent = false): array
    {
        $result = [];

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        foreach ($data as $key => $value) {
            if ($parent) {
                $new_key = sprintf('%s[%s]', $parent, $key);
            } else {
                $new_key = $key;
            }

            if (!$value instanceof CURLFile and (is_array($value) or is_object($value))) {
                $result = array_merge($result, self::buildHTTPCurlQuery($value, $new_key));
            } else {
                $result[$new_key] = $value;
            }
        }

        return $result;
    }

    /**
     * Set the body of the request.
     * @param mixed $data
     * @param bool $asJson
     * @return Request
     * @throws JsonException
     */
    public function withBody(mixed $data, bool $asJson = true): self
    {
        if ($asJson) {
            $this->body = Body::prepareJson($data);
            $this->withHeader('Content-Type', 'application/json');
        } else {
            $this->body = Body::prepareForm($data);
            $this->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        }

        return $this;
    }

    /**
     * Set Basic Authentication for the request.
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function withBasicAuth(string $username, string $password): self
    {
        return $this->withCurlOption(CURLOPT_USERPWD, $username . ':' . $password);
    }

    /**
     * Set a custom cURL option.
     * @param int $option
     * @param mixed $value
     * @return $this
     */
    public function withCurlOption(int $option, mixed $value): self
    {
        $this->customCurlOptions[$option] = $value;
        return $this;
    }

    /**
     * Set a cookie string to specify the contents of a cookie header.
     * @param string $cookie e.g., "fruit=apple; colour=red"
     * @return $this
     */
    public function withCookie(string $cookie): self
    {
        return $this->withCurlOption(CURLOPT_COOKIE, $cookie);
    }

    /**
     * Set a cookie file path for reading and storing cookies across requests.
     * @param string $cookieFile Path with write permissions
     * @return $this
     */
    public function withCookieFile(string $cookieFile): self
    {
        $this->withCurlOption(CURLOPT_COOKIEFILE, $cookieFile);
        $this->withCurlOption(CURLOPT_COOKIEJAR, $cookieFile);

        return $this;
    }

    /**
     * Set the proxy to use for the upcoming request.
     * * @param string $address Proxy address/IP
     * @param int $port Proxy port (default 1080)
     * @param int $type Proxy type (e.g., CURLPROXY_HTTP)
     * @param bool $tunnel Enable tunneling
     * @return $this
     */
    public function withProxy(string $address, int $port = 1080, int $type = CURLPROXY_HTTP, bool $tunnel = false): self
    {
        $this->withCurlOption(CURLOPT_PROXYTYPE, $type);
        $this->withCurlOption(CURLOPT_PROXYPORT, $port);
        $this->withCurlOption(CURLOPT_PROXY, $address);

        if ($tunnel) {
            $this->withCurlOption(CURLOPT_HTTPPROXYTUNNEL, true);
        }

        return $this;
    }

    /**
     * Set the Proxy Authentication.
     * @param string $username
     * @param string $password
     * @param int $authMethod Bitmask (e.g., CURLAUTH_BASIC, CURLAUTH_DIGEST)
     * @return $this
     */
    public function withProxyAuth(string $username, string $password, int $authMethod = CURLAUTH_BASIC): self
    {
        $this->withCurlOption(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        $this->withCurlOption(CURLOPT_PROXYAUTH, $authMethod);

        return $this;
    }

    /**
     * Send the request and return the response as a Response object.
     * @return Response
     * @throws Exception
     */
    public function send(): Response
    {
        $handle = curl_init();

        $options = [
            CURLOPT_URL => self::encodeUrl($this->url),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => $this->method->value,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifyTargetPeer,
            CURLOPT_SSL_VERIFYHOST => $this->verifyTargetHost,
            CURLOPT_HTTPHEADER => $this->getCompiledHeaders(),
        ];

        if ($this->body !== null && $this->method !== Method::GET) {
            $options[CURLOPT_POSTFIELDS] = $this->body;
        }

        foreach ($this->customCurlOptions as $opt => $val) {
            $options[$opt] = $val;
        }

        curl_setopt_array($handle, $options);

        $response = curl_exec($handle);

        if ($response === false) {
            $error = curl_error($handle);
            curl_close($handle);
            throw new Exception($error);
        }

        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);

        curl_close($handle);

        $rawHeaders = substr($response, 0, $headerSize);
        $rawBody = substr($response, $headerSize);

        return new Response($httpCode, $rawBody, $rawHeaders);
    }

    /**
     * Ensure that a URL is encoded and safe to use with cURL
     *
     * @param string $url URL to encode
     *
     * @return string
     */
    private static function encodeUrl(string $url): string
    {
        $url_parsed = parse_url($url);

        $scheme = $url_parsed['scheme'] . '://';
        $host = $url_parsed['host'];
        $port = (strval($url_parsed['port'] ?? null));
        $path = ($url_parsed['path'] ?? null);
        $query = ($url_parsed['query'] ?? null);
        if ($query !== null) {
            $query = '?' . http_build_query(self::getArrayFromQuerystring($query));
        }

        if ($port) {
            $port = ':' . $port;
        }


        return $scheme . $host . $port . $path . $query;
    }

    private static function getArrayFromQuerystring($query): array
    {
        $query = preg_replace_callback('/(?:^|(?<=&))[^=[]+/', function ($match) {
            return bin2hex(urldecode($match[0]));
        }, $query);

        parse_str($query, $values);

        return array_combine(array_map('hex2bin', array_keys($values)), $values);
    }

    /**
     * Compile the headers for cURL.
     * @return array
     */
    private function getCompiledHeaders(): array
    {
        $compiled = [];
        $normalizedGlobals = array_change_key_case(self::$globalHeaders, CASE_LOWER);
        $allHeaders = array_merge($normalizedGlobals, $this->headers);
        foreach ($allHeaders as $key => $value) {
            $compiled[] = "$key: $value";
        }
        return $compiled;
    }

}
