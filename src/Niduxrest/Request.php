<?php

namespace Niduxrest;

use CURLFile;
use CurlHandle;
use Niduxrest\Enum\Method;

class Request
{
    private static $cookie = null;
    private static $cookieFile = null;
    private static array $curlOpts = [];
    private static array $defaultHeaders = [];
    private static CurlHandle|null $handle = null;
    private static array $jsonOpts = [];
    private static $socketTimeout = null;
    private static bool $verifyPeer = true;
    private static bool $verifyHost = true;

    private static array $auth = [
        'user' => '',
        'pass' => '',
        'method' => CURLAUTH_BASIC,
    ];

    private static array $proxy = [
        'port' => false,
        'tunnel' => false,
        'address' => false,
        'type' => CURLPROXY_HTTP,
        'auth' => [
            'user' => '',
            'pass' => '',
            'method' => CURLAUTH_BASIC,
        ],
    ];

    /**
     * Set JSON decode mode
     *
     * @param bool $assoc When TRUE, returned objects will be converted into associative arrays.
     * @param integer $depth User specified recursion depth.
     * @param integer $options Bitmask of JSON decode options. Currently only JSON_BIGINT_AS_STRING is supported (default is to cast large integers as floats)
     *
     */
    public static function setJsonOpts(bool $assoc = false, int $depth = 512, int $options = 0): void
    {
        self::$jsonOpts = [$assoc, $depth, $options];
    }



    /**
     * When true, disables both flags for host and peer verification at once
     * @param bool $ignoreFlags
     * @return void
     */
    public static function setSecurityFlagsOnConnection(bool $ignoreFlags = false): void
    {
        self::$verifyPeer = !$ignoreFlags;
        self::$verifyHost = !$ignoreFlags;
    }


    /**
     * Verify SSL peer
     *
     * @param bool $enabled enable SSL verification, by default is true
     *
     */
    public static function setVerifyPeer(bool $enabled): void
    {
        self::$verifyPeer = $enabled;
    }



    /**
     * Verify SSL host
     *
     * @param bool $enabled enable SSL host verification, by default is true
     *
     */
    public static function setVerifyHost(bool $enabled): void
    {
        self::$verifyHost = $enabled;
    }

    /**
     * Set a timeout
     *
     * @param int|null $seconds timeout value in seconds
     *
     */
    public static function setTimeout(int|null $seconds): void
    {
        self::$socketTimeout = $seconds;
    }

    /**
     * Set default headers to send on every request
     *
     * @param array $headers headers array
     *
     */
    public static function setDefaultHeaders(array $headers): void
    {
        self::$defaultHeaders = array_merge(self::$defaultHeaders, $headers);
    }

    /**
     * Set a new default header to send on every request
     *
     * @param string $name header name
     * @param string $value header value
     *
     */
    public static function setIndidualDefaultHeader($name, $value): void
    {
        self::$defaultHeaders[$name] = $value;
    }

    /**
     * Clear all the default headers
     */
    public static function clearDefaultHeaders(): void
    {
        self::$defaultHeaders = [];
    }

    /**
     * Set curl options to send on every request
     *
     * @param array $options options array
     *
     */
    public static function setCurlOpts(array $options): void
    {
        self::mergeCurlOptions(self::$curlOpts, $options);
    }

    /**
     * Set a new default header to send on every request
     *
     * @param string $name header name
     * @param string $value header value
     *
     */
    public static function setIndividualCurlOpt($name, $value): void
    {
        self::$curlOpts[$name] = $value;
    }

    /**
     * Clear all the default headers
     */
    public static function clearCurlOpts(): void
    {
        self::$curlOpts = [];
    }

    /**
     * Set a Mashape key to send on every request as a header
     * Obtain your Mashape key by browsing one of your Mashape applications on https://www.mashape.com
     *
     * Note: Mashape provides 2 keys for each application: a 'Testing' and a 'Production' one.
     *       Be aware of which key you are using and do not share your Production key.
     *
     * @param string $key Mashape key
     *
     */
    public static function setMashapeKey(string $key): void
    {
        self::setIndidualDefaultHeader('X-Mashape-Key', $key);
    }

    /**
     * Set a bearer token for your request
     *
     * @param string $bearerToken "bearer Token"
     *
     */
    public static function setBearerToken(string $bearerToken): void
    {
        self::setIndidualDefaultHeader('Authorization', "Bearer " . $bearerToken);
    }

    /**
     * Set a cookie string for enabling cookie handling
     *
     * @param string $cookie
     */
    public static function setCookie(string $cookie)
    {
        self::$cookie = $cookie;
    }

    /**
     * Set a cookie file path for enabling cookie handling
     *
     * $cookieFile must be a correct path with write permission
     *
     * @param string $cookieFile - path to file for saving cookie
     */
    public static function setCookieFile(string $cookieFile)
    {
        self::$cookieFile = $cookieFile;
    }

    /**
     * Set authentication method to use
     *
     * @param string $username authentication username
     * @param string $password authentication password
     * @param integer $method authentication method
     */
    public static function setAuthenticationMethod($username = '', $password = '', $method = CURLAUTH_BASIC)
    {
        self::$auth['user'] = $username;
        self::$auth['pass'] = $password;
        self::$auth['method'] = $method;
    }

    /**
     * Set proxy to use
     *
     * @param string $address proxy address
     * @param integer $port proxy port
     * @param integer $type (Available options for this are CURLPROXY_HTTP, CURLPROXY_HTTP_1_0 CURLPROXY_SOCKS4, CURLPROXY_SOCKS5, CURLPROXY_SOCKS4A and CURLPROXY_SOCKS5_HOSTNAME)
     * @param bool $tunnel enable/disable tunneling
     */
    public static function setProxy($address, $port = 1080, $type = CURLPROXY_HTTP, $tunnel = false)
    {
        self::$proxy['type'] = $type;
        self::$proxy['port'] = $port;
        self::$proxy['tunnel'] = $tunnel;
        self::$proxy['address'] = $address;
    }

    /**
     * Set proxy authentication method to use
     *
     * @param string $username authentication username
     * @param string $password authentication password
     * @param integer $method authentication method
     */
    public static function setProxyAuthentication($username = '', $password = '', $method = CURLAUTH_BASIC)
    {
        self::$proxy['auth']['user'] = $username;
        self::$proxy['auth']['pass'] = $password;
        self::$proxy['auth']['method'] = $method;
    }

    /**
     * Send a GET request to a URL
     *
     * @param string $url URL to send the GET request to
     * @param array $headers additional headers to send
     * @param mixed|null $parameters parameters to send in the querystring
     * @param string|null $username Authentication username (deprecated)
     * @param string|null $password Authentication password (deprecated)
     *
     * @return Response
     * @throws Exception
     */
    public static function get(string $url, array $headers = [], mixed $parameters = null, string $username = null, string $password = null): Response
    {
        return self::send(Method::GET, trim($url), $parameters, $headers, $username, $password);
    }

    /**
     * Send a HEAD request to a URL
     *
     * @param string $url URL to send the HEAD request to
     * @param array $headers additional headers to send
     * @param mixed $parameters parameters to send in the querystring
     * @param string $username Basic Authentication username (deprecated)
     * @param string $password Basic Authentication password (deprecated)
     *
     * @return Response
     * @throws Exception
     */
    public static function head($url, $headers = [], $parameters = null, $username = null, $password = null): Response
    {
        return self::send(Method::HEAD, $url, $parameters, $headers, $username, $password);
    }

    /**
     * Send a OPTIONS request to a URL
     *
     * @param string $url URL to send the OPTIONS request to
     * @param array $headers additional headers to send
     * @param mixed $parameters parameters to send in the querystring
     * @param string $username Basic Authentication username
     * @param string $password Basic Authentication password
     *
     * @return Response
     * @throws Exception
     */
    public static function options($url, $headers = [], $parameters = null, $username = null, $password = null): Response
    {
        return self::send(Method::OPTIONS, $url, $parameters, $headers, $username, $password);
    }

    /**
     * Send a CONNECT request to a URL
     *
     * @param string $url URL to send the CONNECT request to
     * @param array $headers additional headers to send
     * @param mixed $parameters parameters to send in the querystring
     * @param string $username Basic Authentication username (deprecated)
     * @param string $password Basic Authentication password (deprecated)
     *
     * @return Response
     * @throws Exception
     */
    public static function connect($url, $headers = [], $parameters = null, $username = null, $password = null): Response
    {
        return self::send(Method::CONNECT, $url, $parameters, $headers, $username, $password);
    }

    /**
     * Send POST request to a URL
     *
     * @param string $url URL to send the POST request to
     * @param array $headers additional headers to send
     * @param mixed $body POST body data
     * @param string $username Basic Authentication username (deprecated)
     * @param string $password Basic Authentication password (deprecated)
     *
     * @return Response response
     * @throws Exception
     */
    public static function post($url, $headers = [], $body = null, $username = null, $password = null): Response
    {
        return self::send(Method::POST, $url, $body, $headers, $username, $password);
    }

    /**
     * Send DELETE request to a URL
     *
     * @param string $url URL to send the DELETE request to
     * @param array $headers additional headers to send
     * @param mixed $body DELETE body data
     * @param string $username Basic Authentication username (deprecated)
     * @param string $password Basic Authentication password (deprecated)
     *
     * @return Response
     * @throws Exception
     */
    public static function delete($url, $headers = [], $body = null, $username = null, $password = null): Response
    {
        return self::send(Method::DELETE, $url, $body, $headers, $username, $password);
    }

    /**
     * Send PUT request to a URL
     *
     * @param string $url URL to send the PUT request to
     * @param array $headers additional headers to send
     * @param mixed $body PUT body data
     * @param string $username Basic Authentication username (deprecated)
     * @param string $password Basic Authentication password (deprecated)
     *
     * @return Response
     * @throws Exception
     */
    public static function put($url, $headers = [], $body = null, $username = null, $password = null): Response
    {
        return self::send(Method::PUT, $url, $body, $headers, $username, $password);
    }

    /**
     * Send PATCH request to a URL
     *
     * @param string $url URL to send the PATCH request to
     * @param array $headers additional headers to send
     * @param mixed $body PATCH body data
     * @param string $username Basic Authentication username (deprecated)
     * @param string $password Basic Authentication password (deprecated)
     *
     * @return Response
     * @throws Exception
     */
    public static function patch($url, $headers = [], $body = null, $username = null, $password = null): Response
    {
        return self::send(Method::PATCH, $url, $body, $headers, $username, $password);
    }

    /**
     * Send TRACE request to a URL
     *
     * @param string $url URL to send the TRACE request to
     * @param array $headers additional headers to send
     * @param mixed $body TRACE body data
     * @param string $username Basic Authentication username (deprecated)
     * @param string $password Basic Authentication password (deprecated)
     *
     * @return Response
     * @throws Exception
     */
    public static function trace($url, $headers = [], $body = null, $username = null, $password = null): Response
    {
        return self::send(Method::TRACE, $url, $body, $headers, $username, $password);
    }

    /**
     * This function is useful for serializing multidimensional arrays, and avoid getting
     * the 'Array to string conversion' notice
     *
     * @param array|object $data array to flatten.
     * @param bool|string $parent parent key or false if no parent
     *
     * @return array
     */
    public static function buildHTTPCurlQuery($data, $parent = false): array
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
     * Send a cURL request
     *
     * @param Method|string $method HTTP method to use
     * @param string $url URL to send the request to
     * @param mixed $body request body
     * @param array $headers additional headers to send
     * @param string $username Authentication username (deprecated)
     * @param string $password Authentication password (deprecated)
     *
     * @return Response
     * @throws Exception if a cURL error occurs
     */
    public static function send($method, $url, $body = null, $headers = [], $username = null, $password = null): Response
    {
        self::$handle = curl_init();

        if ($method !== Method::GET) {
            if ($method === Method::POST) {
                curl_setopt(self::$handle, CURLOPT_POST, true);
            } else {
                if ($method === Method::HEAD) {
                    curl_setopt(self::$handle, CURLOPT_NOBODY, true);
                }
                curl_setopt(self::$handle, CURLOPT_CUSTOMREQUEST, $method->value);
            }

            curl_setopt(self::$handle, CURLOPT_POSTFIELDS, $body);
        } else if (is_array($body)) {
            if (str_contains($url, '?')) {
                $url .= '&';
            } else {
                $url .= '?';
            }

            $url .= urldecode(http_build_query(self::buildHTTPCurlQuery($body)));
        }

        $curl_base_options = [
            CURLOPT_URL => self::encodeUrl($url),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_HTTPHEADER => self::getFormattedHeaders($headers),
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => self::$verifyPeer,
            CURLOPT_SSL_VERIFYHOST => self::$verifyHost === false ? 0 : 2,
            CURLOPT_ENCODING => '',
        ];

        curl_setopt_array(self::$handle, self::mergeCurlOptions($curl_base_options, self::$curlOpts));

        if (self::$socketTimeout !== null) {
            curl_setopt(self::$handle, CURLOPT_TIMEOUT, self::$socketTimeout);
        }

        if (self::$cookie) {
            curl_setopt(self::$handle, CURLOPT_COOKIE, self::$cookie);
        }

        if (self::$cookieFile) {
            curl_setopt(self::$handle, CURLOPT_COOKIEFILE, self::$cookieFile);
            curl_setopt(self::$handle, CURLOPT_COOKIEJAR, self::$cookieFile);
        }

        // supporting deprecated http auth method
        if (!empty($username)) {
            curl_setopt_array(self::$handle, [
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => $username . ':' . $password,
            ]);
        }

        if (!empty(self::$auth['user'])) {
            curl_setopt_array(self::$handle, [
                CURLOPT_HTTPAUTH => self::$auth['method'],
                CURLOPT_USERPWD => self::$auth['user'] . ':' . self::$auth['pass'],
            ]);
        }

        if (self::$proxy['address'] !== false) {
            curl_setopt_array(self::$handle, [
                CURLOPT_PROXYTYPE => self::$proxy['type'],
                CURLOPT_PROXY => self::$proxy['address'],
                CURLOPT_PROXYPORT => self::$proxy['port'],
                CURLOPT_HTTPPROXYTUNNEL => self::$proxy['tunnel'],
                CURLOPT_PROXYAUTH => self::$proxy['auth']['method'],
                CURLOPT_PROXYUSERPWD => self::$proxy['auth']['user'] . ':' . self::$proxy['auth']['pass'],
            ]);
        }

        $response = curl_exec(self::$handle);
        $error = curl_error(self::$handle);
        $info = self::getInfo();

        if ($error) {
            throw new Exception($error);
        }
        // Split the full response in its headers and body
        $header_size = $info['header_size'];
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $httpCode = $info['http_code'];


        return new Response($httpCode, $body, $header, self::$jsonOpts);
    }

    public static function getInfo($opt = false)
    {
        return ($opt) ? curl_getinfo(self::$handle, $opt) : curl_getinfo(self::$handle);
    }

    public static function getCurlHandle(): ?CurlHandle
    {
        return self::$handle;
    }

    public static function getFormattedHeaders($headers): array
    {
        $formattedHeaders = [];

        $combinedHeaders = array_change_key_case(array_merge(self::$defaultHeaders, (array)$headers));

        foreach ($combinedHeaders as $key => $val) {
            $formattedHeaders[] = self::getHeaderString($key, $val);
        }

        if (!array_key_exists('user-agent', $combinedHeaders)) {
            $formattedHeaders[] = 'user-agent: niduxrest-php/1.0';
        }

        if (!array_key_exists('expect', $combinedHeaders)) {
            $formattedHeaders[] = 'expect:';
        }

        return $formattedHeaders;
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
     * Ensure that a URL is encoded and safe to use with cURL
     *
     * @param string $url URL to encode
     *
     * @return string
     */
    private static function encodeUrl($url): string
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

    private static function getHeaderString($key, $val): string
    {
        $key = trim(strtolower($key));
        return $key . ': ' . $val;
    }

    /**
     * @param array $existing_options
     * @param array $new_options
     *
     * @return array
     */
    private static function mergeCurlOptions(&$existing_options, $new_options): array
    {
        $existing_options = $new_options + $existing_options;
        return $existing_options;
    }

    public static function getJsonOpts(): array
    {
        return self::$jsonOpts;
    }


    /** Deprecated section to be removed on version 2.1 */

    /**
     * @deprecated Use setJsonOpts instead
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     * @return void
     */
    public static function jsonOpts(bool $assoc = false, int $depth = 512, int $options = 0): void
    {
        self::setJsonOpts($assoc, $depth , $options);
    }

    /**
     * @deprecated Use setVerifyPeer instead
     * @param bool $enabled
     * @return void
     */
    public static function verifyPeer(bool $enabled): void
    {
        self::setVerifyPeer($enabled);
    }

    /**
     * @deprecated Use setVerifyHost instead
     * @param bool $enabled
     * @return void
     */
    public static function verifyHost(bool $enabled): void
    {
        self::setVerifyHost($enabled);
    }

    /**
     * @deprecated
     * Set a timeout
     *
     * @param int|null $seconds timeout value in seconds
     *
     */
    public static function timeout(int|null $seconds): void
    {
        self::setTimeout($seconds);
    }

    /**
     * @deprecated
     * Set default headers to send on every request
     *
     * @param array $headers headers array
     *
     */
    public static function defaultHeaders(array $headers): void
    {
        self::setDefaultHeaders($headers);
    }

    /**
     * @deprecated
     * Set a new default header to send on every request
     *
     * @param string $name header name
     * @param string $value header value
     *
     */
    public static function defaultHeader($name, $value): void
    {
        self::setIndidualDefaultHeader($name, $value);
    }


    /**
     * @deprecated
     * Set curl options to send on every request
     *
     * @param array $options options array
     *
     */
    public static function curlOpts(array $options): void
    {
        self::setCurlOpts($options);
    }

    /**
     * @deprecated
     * Set a new default header to send on every request
     *
     * @param string $name header name
     * @param string $value header value
     *
     */
    public static function curlOpt($name, $value): void
    {
        self::setIndividualCurlOpt($name, $value);
    }




    /**
     * @deprecated
     * Set a cookie string for enabling cookie handling
     *
     * @param string $cookie
     */
    public static function cookie(string $cookie)
    {
        self::setCookie($cookie);
    }

    /**
     * @deprecated
     * Set a cookie file path for enabling cookie handling
     *
     * $cookieFile must be a correct path with write permission
     *
     * @param string $cookieFile - path to file for saving cookie
     */
    public static function cookieFile(string $cookieFile)
    {
        self::setCookieFile($cookieFile);
    }

    /**
     * @deprecated
     * Set authentication method to use
     *
     * @param string $username authentication username
     * @param string $password authentication password
     * @param integer $method authentication method
     */
    public static function auth($username = '', $password = '', $method = CURLAUTH_BASIC)
    {
        self::setAuthenticationMethod($username, $password, $method);
    }

    /**
     * @deprecated
     * Set proxy to use
     *
     * @param string $address proxy address
     * @param integer $port proxy port
     * @param integer $type (Available options for this are CURLPROXY_HTTP, CURLPROXY_HTTP_1_0 CURLPROXY_SOCKS4, CURLPROXY_SOCKS5, CURLPROXY_SOCKS4A and CURLPROXY_SOCKS5_HOSTNAME)
     * @param bool $tunnel enable/disable tunneling
     */
    public static function proxy($address, $port = 1080, $type = CURLPROXY_HTTP, $tunnel = false)
    {
        self::setProxy($address, $port, $type, $tunnel);
    }

    /**
     * @deprecated
     * Set proxy authentication method to use
     *
     * @param string $username authentication username
     * @param string $password authentication password
     * @param integer $method authentication method
     */
    public static function proxyAuth($username = '', $password = '', $method = CURLAUTH_BASIC)
    {
        self::setProxyAuthentication($username, $password, $method);
    }

}
