# NiduxRest PHP Client

A fast, lightweight, and modern HTTP client for PHP 8.3+.
Originally a fork from Kong's Unirest, NiduxRest has been completely rewritten from the ground up in v3.0 to embrace
strict typing, isolated instances, and a beautiful fluent interface.

> **ATTENTION: BREAKING CHANGES (v3.0.0)**
> Version 3.0 is a complete architectural overhaul.
> * **New Namespace:** The namespace has been upgraded from `Niduxrest` to `Nidux\Rest` to comply with modern PSR-4
    standards.
> * **No More Global State:** All static methods for requests (`Request::post()`, `Request::setJsonOpts()`, etc.) have
    been removed. The client now uses a strictly fluent, instance-based approach.
> * **Response Handling:** Responses are now predictable DTOs. Direct property access (e.g., `$response->code`) is
    removed in favor of strict getters.
> * **Deprecation removal:** All previous methods marked as deprecated have been removed.

If your project still relies on the classic static calls, please lock your `composer.json` to
`"nidux/niduxrest-php": "^2.0"`, then perform a refactoring on non-production environments.

---

## The Fluent Advantage: v3 vs v2

Why the rewrite? In enterprise SaaS environments, relying on static global states (like setting global timeouts or
headers) leads to unpredictable bugs when multiple API calls are made in the same lifecycle.
Here on Nidux, we've encountered many of these bugs, leading to critical and weird issues in production. We did solve
this problem by working around over version 2, but it was not a realistic solution for us. NiduxRest was needing a
complete rewrite.

v3 introduces isolated instances with a fluent builder pattern.

**The Old Way (v2.0):** Positional arguments, easy to forget the order, relies on global state affecting all future
requests in the same script.

```php
// Setting global state (Dangerous in large apps)
\Niduxrest\Request::setTimeout(10); 
$response = \Niduxrest\Request::post('https://api.example.com', ['Accept' => 'application/json'], $body);
```

**The New Way (v3.0.0):** Expressive, isolated, and IDE-friendly.

```php
use Nidux\Rest\Request;

$response = Request::new()
    ->post('https://api.example.com')
    ->withHeader('Accept', 'application/json')
    ->withBearerToken('super-secret-token')
    ->timeout(10)
    ->withBody(['name' => 'John Doe'])
    ->send();
```

## Requirements

- [cURL](http://php.net/manual/en/book.curl.php)
- PHP 8.3+

# Documentation

Since this is a complete rewrite, the documentation is now focused on the new fluent interface. So if you need to refer
to the old documentation, please refer to the [v2.0.1](https://github.com/Nidux/NiduxRest/tree/v2.0.1) tag.

## Installation

### Using [Composer](https://getcomposer.org)

To install NiduxRest with Composer, just add the following to your `composer.json` file:

```json
{
  "require": {
    "nidux/niduxrest-php": "^3.0"
  }
}
```

or by running the following command:

```shell
composer require nidux/niduxrest-php
```

## Basic Usage

### Making Requests

You can start a request using the `new()` static constructor and chain your configurations. You can use a very specific
fluent approach or use some of the available helpers:

```php
use Nidux\Rest\Request;
use Nidux\Rest\Enum\Method;

// GET Request with Query Parameters 
$response = Request::new()
    ->to('https://postman-echo.com/get')
    ->withQuery(['search' => 'laptop', 'limit' => 10])
    ->send();

// POST Request (JSON by default)
$response = Request::new()
    ->to('https://postman-echo.com/post')
    ->withMethod(Method::POST)
    ->withBody(['sku' => '12345', 'price' => 99.99])
    ->send();


// GET Request with Query Parameters (with Helper)
$response = Request::new()
    ->get('https://postman-echo.com/get')
    ->withQuery(['search' => 'laptop', 'limit' => 10])
    ->send();

// POST Request (JSON by default) (with Helper)
$response = Request::new()
    ->post('https://postman-echo.com/post')
    ->withBody(['sku' => '12345', 'price' => 99.99])
    ->send();
```

*Available HTTP method helpers:* `->get()`, `->post()`, `->put()`, `->patch()`, `->delete()`, `->head()`, `->options()`,
`->trace()`.

### Request Bodies

The `withBody()` method takes two arguments: the data array/object, and a boolean `$asJson` (default `true`).

```php
// Send as application/json (Default)
->withBody(['name' => 'John']); 

// Send as application/x-www-form-urlencoded
->withBody(['name' => 'John'], false);
```

### Multipart & File Uploads

For `multipart/form-data` and file uploads, use the `withMultipartBody()` helper alongside the `Body::prepareFile()`
utility:

```php
use Nidux\Rest\Request;
use Nidux\Rest\Request\Body;

$response = Request::new()
    ->post('https://api.example.com/upload')
    ->withMultipartBody([
        'username' => 'ahmad',
        'avatar' => Body::prepareFile('/path/to/avatar.jpg', 'image/jpeg')
    ])
    ->send();
```

---

### The Response Object

NiduxRest v3 respects that different developers prefer different data structures. The Response object parses JSON
automatically and allows you to retrieve data in three different ways:

```php
$response = Request::new()->get('https://api.example.com/users/1')->send();

// 1. The Standard Object (stdClass)
$user = $response->getBody();
echo $user->name;

// 2. The Associative Array 
$userArray = $response->getArray();
echo $userArray['name'];

// 3. The Raw String (Great for XML, CSV, or custom parsing)
$raw = $response->getRawBody();
```

### Status Helpers (Brand New!!)

```php
if ($response->isSuccessful()) { // Validates HTTP Codes from 200-299
    // Do something
}

if ($response->isClientError()) { // Validates HTTP Codes from 400-499
    echo "Check your payload!";
}

if ($response->isServerError()) { // Validates HTTP Codes from 500+
    echo "The external API is down.";
}

// Or get the exact code/headers
$response->getCode();    // e.g., 200
$response->getHeaders(); // Returns array of headers
```

---

### Advanced Configuration

Because the client is fluent, all configurations are isolated strictly to the instance being executed.

#### Authentication
```php
// Bearer Token
Request::new()->withBearerToken('your-jwt-token')->...

// Basic Auth
Request::new()->withBasicAuth('username', 'password')->...
```

#### Proxies and Cookies

```php
// Route traffic through a proxy
Request::new()
    ->withProxy('10.10.10.1', 8080)
    ->withProxyAuth('user', 'pass')
    ->...

// Read/Store cookies across requests (Session persistence)
Request::new()->withCookieFile('/path/to/cookie.txt')->...

// Send a manual cookie string
Request::new()->withCookie('fruit=apple; session=123')->...
```

#### Custom cURL Options (The Escape Hatch)

If you need to define specific cURL behaviors that don't have a dedicated helper, you can inject native `CURLOPT_*`
constants directly into the fluent chain:

```php
Request::new()
    ->get('https://api.example.com')
    ->withCurlOption(CURLOPT_ENCODING, 'gzip')
    ->withCurlOption(CURLOPT_TCP_KEEPALIVE, 1)
    ->send();
```

##### Security (SSL) and Hostname Verification

SSL verification is **enabled by default**. If you need to hit a local testing server with self-signed certificates:

```php
Request::new()
    ->get('https://local.dev.nidux.com')
    ->setPeerVerification(false)
    ->setHostVerification(false)
    ->send();
```

## Contributing & Support

NiduxRest is still evolving driven by the needs of the whole Nidux ecosystem, but it is proudly open-sourced to give
back to the PHP community. We are absolutely open to improvements, bug fixes, and new ideas!

If you want to contribute to this project:

* **Found a bug?** Please open an [Issue](https://github.com/nidux/niduxrest/issues) on GitHub with a clear description
  of the problem and, if possible, the steps to reproduce it.
* **Have a feature request or improvement?** Feel free to open an Issue to discuss it.
* **Want to write some code?** We welcome Pull Requests! Just make sure to run the PHPUnit test suite before submitting
  your PR to ensure everything stays green.

Let's build a better, faster HTTP client together.