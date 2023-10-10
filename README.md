# NiduxRest for PHP 8.1+ (based on [Unirest](http://unirest.io) from Kong)

This is a fork from Unirest that I try to keep it updated as possible. The main idea is to
continue with the idea of something quick and simple as originally made by [Mashape](https://github.com/Mashape).

**For PHP versions like 8.0 and below, keep using v1.0.5.**

## Notable changes

* PHP 8.1 support
* Optimization
* Breaking changes **See below**

# Breaking changes

* I did remove access to properties directly, trying to follow some Object-oriented best practices, so use the proper *GET* methods instead.
* I did rename setters to a proper naming convention
* There was an unnecesary return on some setters, these were completely removed on v2
* Changeing methods from interface to enum


## Requirements

- [cURL](http://php.net/manual/en/book.curl.php)
- PHP 8.1+

# Documentation

I will keep most of the original documentation for simplicity, I will update the example as I begin making improvements

## Installation

### Using [Composer](https://getcomposer.org)

To install unirest-php with Composer, just add the following to your `composer.json` file:

```json
{
  "require-dev": {
    "nidux/niduxrest-php": "2.*"
  }
}
```

or by running the following command:

```shell
composer require nidux/niduxrest-php
```

## Usage

### Creating a Request

So you're probably wondering how using Niduxrest makes creating requests in PHP easier, let's look at a working example:

```php
$headers = ['Accept' => 'application/json'];
$query = ['foo' => 'hello', 'bar' => 'world'];

$response = Niduxrest\Request::post('http://mockbin.com/request', $headers, $query);

$response->getCode();        // HTTP Status code via getter
$response->getHeaders();     // Headers via getter
$response->getBody();        // Parsed body via getter
$response->getRawBody();    // Unparsed body via getter
```
**PAY ATTENTION HERE: You cannot access the properties directly anymore**


### JSON Requests *(`application/json`)*

A JSON Request can be constructed using the `Niduxrest\Request\Body::prepareJson` helper:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'ahmad', 'company' => 'mashape'];

$body = Niduxrest\Request\Body::prepareJson($data);

$response = Niduxrest\Request::post('http://mockbin.com/request', $headers, $body);
```

**Notes:**

- `Content-Type` headers will be automatically set to `application/json`
- the data variable will be processed through [`json_encode`](http://php.net/manual/en/function.json-encode.php) with
  default values for arguments.
- an error will be thrown if the [JSON Extension](http://php.net/manual/en/book.json.php) is not available.

### Form Requests *(`application/x-www-form-urlencoded`)*

A typical Form Request can be constructed using the `Niduxrest\Request\Body::prepareForm` helper:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'ahmad', 'company' => 'mashape'];

$body = Niduxrest\Request\Body::prepareForm($data);

$response = Niduxrest\Request::post('http://mockbin.com/request', $headers, $body);
```

**Notes:**

- `Content-Type` headers will be automatically set to `application/x-www-form-urlencoded`
- the final data array will be processed
  through [`http_build_query`](http://php.net/manual/en/function.http-build-query.php) with default values for
  arguments.

### Multipart Requests *(`multipart/form-data`)*

A Multipart Request can be constructed using the `Niduxrest\Request\Body::prepareMultiPart` helper:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'ahmad', 'company' => 'mashape'];

$body = Niduxrest\Request\Body::prepareMultiPart($data);

$response = Niduxrest\Request::post('http://mockbin.com/request', $headers, $body);
```

**Notes:**

- `Content-Type` headers will be automatically set to `multipart/form-data`.
- an auto-generated `--boundary` will be set.

### Multipart File Upload

simply add an array of files as the second argument to to the `prepareMultiPart` helper:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'ahmad', 'company' => 'mashape'];
$files = ['bio' => '/path/to/bio.txt', 'avatar' => '/path/to/avatar.jpg'];

$body = Niduxrest\Request\Body::prepareMultiPart($data, $files);

$response = Niduxrest\Request::post('http://mockbin.com/request', $headers, $body);
 ```

If you wish to further customize the properties of files uploaded you can do so with the `Niduxrest\Request\Body::prepareFile`
helper:

```php
$headers = ['Accept' => 'application/json'];
$body = [
    'name' => 'ahmad', 
    'company' => 'mashape'
    'bio' => Niduxrest\Request\Body::prepareFile('/path/to/bio.txt', 'text/plain'),
    'avatar' => Niduxrest\Request\Body::prepareFile('/path/to/my_avatar.jpg', 'text/plain', 'avatar.jpg')
];

$response = Niduxrest\Request::post('http://mockbin.com/request', $headers, $body);
 ```

**Note**: we did not use the `Niduxrest\Request\Body::multipart` helper in this example, it is not needed when manually
adding files.

### Custom Body

Sending a custom body such rather than using the `Niduxrest\Request\Body` helpers is also possible, for example, using
a [`serialize`](http://php.net/manual/en/function.serialize.php) body string with a custom `Content-Type`:

```php
$headers = ['Accept' => 'application/json', 'Content-Type' => 'application/x-php-serialized'];
$body = serialize((['foo' => 'hello', 'bar' => 'world']);

$response = Niduxrest\Request::post('http://mockbin.com/request', $headers, $body);
```

### Authentication examples

##### Bearer token authentication

For simplicity, there is a new method to set a bearer token to the Request

```php
// auth with bearer token
Niduxrest\Request::setBearerToken('exampleOfSuperSecretBearerToken');
```

##### Basic authentication

```php
// basic auth
Niduxrest\Request::setAuthenticationMethod('username', 'password');
```

##### Using the mashape key for their services (you need to get the key first)

```php
// Mashape auth
Niduxrest\Request::setMashapeKey('<mashape_key>');
```

The third parameter, which is a bitmask, will Niduxrest which HTTP authentication method(s) you want it to use for your
proxy authentication.

If more than one bit is set, Niduxrest *(at PHP's libcurl level)* will first query the site to see what authentication
methods it supports and then pick the best one you allow it to use. *For some methods, this will induce an extra network
round-trip.*

**Supported Methods**

| Method                                                                                                           | Description                                                                                                                                                                                 |
|------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `CURLAUTH_BASIC`                                                                                                 | HTTP Basic authentication. This is the default choice                                                                                                                                       | 
| `CURLAUTH_DIGEST`                                                                                                | HTTP Digest authentication. as defined in [RFC 2617](http://www.ietf.org/rfc/rfc2617.txt)                                                                                                   | 
| `CURLAUTH_DIGEST_IE`                                                                                             | HTTP Digest authentication with an IE flavor. *The IE flavor is simply that libcurl will use a                                                                                              |
| special "quirk" that IE is known to have used before version 7 and that some servers require the client to use.* |                                                                                                                                                                                             |
| `CURLAUTH_NEGOTIATE`                                                                                             | HTTP Negotiate (SPNEGO) authentication. as defined in [RFC 4559](http://www.ietf.org/rfc/rfc4559.txt)                                                                                       |
| `CURLAUTH_NTLM`                                                                                                  | HTTP NTLM authentication. A proprietary protocol invented and used by Microsoft.                                                                                                            |
| `CURLAUTH_NTLM_WB`                                                                                               | NTLM delegating to winbind helper. Authentication is performed by a separate binary application. *                                                                                          |
| see [libcurl docs](http://curl.haxx.se/libcurl/c/CURLOPT_HTTPAUTH.html) for more info*                           |                                                                                                                                                                                             |
| `CURLAUTH_ANY`                                                                                                   | This is a convenience macro that sets all bits and thus makes libcurl pick any it finds suitable. libcurl will automatically select the one it finds most secure.                           |
| `CURLAUTH_ANYSAFE`                                                                                               | This is a convenience macro that sets all bits except Basic and thus makes libcurl pick any it finds suitable. libcurl will automatically select the one it finds most secure.              |
| `CURLAUTH_ONLY`                                                                                                  | This is a meta symbol. OR this value together with a single specific auth value to force libcurl to probe for un-restricted auth and if not, only that single auth algorithm is acceptable. |

```php
// custom auth method
Niduxrest\Request::setProxyAuthentication('username', 'password', CURLAUTH_DIGEST);
```

Previous versions of **Niduxrest** support *Basic Authentication* by providing the `username` and `password` arguments:

```php
$response = Niduxrest\Request::get('http://mockbin.com/request', null, null, 'username', 'password');
```

**This has been deprecated, and will be completely removed in `v.2.0` please use the `Niduxrest\Request::auth()`
method instead**

### Cookies

Set a cookie string to specify the contents of a cookie header. Multiple cookies are separated with a semicolon followed
by a space (e.g., "fruit=apple; colour=red")

```php
Niduxrest\Request::setCookie($cookie)
```

Set a cookie file path for enabling cookie reading and storing cookies across multiple sequence of requests.

```php
Niduxrest\Request::setCookieFile($cookieFile)
```

`$cookieFile` must be a correct path with write permission.

### Request Object

```php
Niduxrest\Request::get($url, $headers = [], $parameters = null)
Niduxrest\Request::post($url, $headers = [], $body = null)
Niduxrest\Request::put($url, $headers = [], $body = null)
Niduxrest\Request::patch($url, $headers = [], $body = null)
Niduxrest\Request::delete($url, $headers = [], $body = null)
```

- `url` - Endpoint, address, or uri to be acted upon and requested information from.
- `headers` - Request Headers as associative array or object
- `body` - Request Body as associative array or object

You can send a request with any [standard](http://www.iana.org/assignments/http-methods/http-methods.xhtml) or custom
HTTP Method present on the Method enum:

```php
Niduxrest\Request::send(Niduxrest\Enum\Method::LINK, $url, $headers = [], $body);
Niduxrest\Request::send(Niduxrest\Enum\Method::CHECKOUT, $url, $headers = [], $body);
Niduxrest\Request::send(Niduxrest\Enum\Method::HEAD, $url, $headers = [], $body);
Niduxrest\Request::send(Niduxrest\Enum\Method::LOCK, $url, $headers = [], $body);
```

### Response Object

Upon recieving a response, Niduxrest returns a Response Object, this object will have the following getters available.

- `getCode()` - HTTP Response Status Code (Example `200`)
- `getHeaders()` - HTTP Response Headers
- `getBody()` - Parsed response body where applicable, for example JSON responses are parsed to Objects / Associative Arrays.
- `getRawBody()` - Un-parsed response body

### Advanced Configuration

You can set some advanced configuration to tune Niduxrest-PHP:

#### Custom JSON Decode Flags

Niduxrest uses PHP's [JSON Extension](http://php.net/manual/en/book.json.php) for automatically decoding JSON responses.
sometime you may want to return associative arrays, limit the depth of recursion, or use any of
the [customization flags](http://php.net/manual/en/json.constants.php).

To do so, simply set the desired options using the `setJsonOpts` request method:

```php
Niduxrest\Request::setJsonOpts(true, 512, JSON_NUMERIC_CHECK & JSON_FORCE_OBJECT & JSON_UNESCAPED_SLASHES);
```

#### Timeout

You can set a custom timeout value (in **seconds**):

```php
Niduxrest\Request::setTimeout(5); // 5s timeout
```

#### Proxy

Set the proxy to use for the upcoming request.

you can also set the proxy type to be one of `CURLPROXY_HTTP`, `CURLPROXY_HTTP_1_0`, `CURLPROXY_SOCKS4`
, `CURLPROXY_SOCKS5`, `CURLPROXY_SOCKS4A`, and `CURLPROXY_SOCKS5_HOSTNAME`.

*check the [cURL docs](http://curl.haxx.se/libcurl/c/CURLOPT_PROXYTYPE.html) for more info*.

```php
// quick setup with default port: 1080
Niduxrest\Request::setProxy('10.10.10.1');

// custom port and proxy type
Niduxrest\Request::setProxy('10.10.10.1', 8080, CURLPROXY_HTTP);

// enable tunneling
Niduxrest\Request::setProxy('10.10.10.1', 8080, CURLPROXY_HTTP, true);
```

##### Proxy Authenticaton

Passing a username, password *(optional)*, defaults to Basic Authentication:

```php
// basic auth
Niduxrest\Request::setProxyAuthentication('username', 'password');
```

The third parameter, which is a bitmask, will Niduxrest which HTTP authentication method(s) you want it to use for your
proxy authentication.

If more than one bit is set, Niduxrest *(at PHP's libcurl level)* will first query the site to see what authentication
methods it supports and then pick the best one you allow it to use. *For some methods, this will induce an extra network
round-trip.*

See [Authentication](#authentication) for more details on methods supported.

```php
// basic auth
Niduxrest\Request::setProxyAuthentication('username', 'password', CURLAUTH_DIGEST);
```

#### Default Request Headers

You can set default headers that will be sent on every request:

```php
Niduxrest\Request::setIndidualDefaultHeader('Header1', 'Value1');
Niduxrest\Request::setIndidualDefaultHeader('Header2', 'Value2');
```

You can set default headers in bulk by passing an array:

```php
Niduxrest\Request::setDefaultHeaders([
    'Header1' => 'Value1',
    'Header2' => 'Value2'
]);
```

You can clear the default headers anytime with:

```php
Niduxrest\Request::clearDefaultHeaders();
```

#### Default cURL Options

You can set default [cURL options](http://php.net/manual/en/function.curl-setopt.php) that will be sent on every
request:

```php
Niduxrest\Request::setIndividualCurlOpt(CURLOPT_COOKIE, 'foo=bar');
```

You can set options bulk by passing an array:

```php
Niduxrest\Request::setCurlOpts([
    CURLOPT_COOKIE => 'foo=bar'
]);
```

You can clear the default options anytime with:

```php
Niduxrest\Request::clearCurlOpts();
```

#### SSL validation

You can explicitly enable or disable SSL certificate validation when consuming an SSL protected endpoint:

```php
Niduxrest\Request::setVerifyPeer(false); // Disables SSL cert validation
```

By default is `true`.

#### Utility Methods

```php
// alias for `curl_getinfo`
Niduxrest\Request::getInfo()

// returns internal cURL handle
Niduxrest\Request::getCurlHandle()
```

