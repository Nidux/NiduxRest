<?php

namespace Nidux\Rest\Tests;

use Nidux\Rest\Enum\Method;
use Nidux\Rest\Exception;
use Nidux\Rest\Request as Request;
use Nidux\Rest\Request\Body;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    // Generic
    public function testCurlOpts(): void
    {
        $response = Request::new()
            ->to('https://postman-echo.com/get')
            ->withCurlOption(CURLOPT_COOKIE, 'foo=bar')
            ->send();

        $this->assertEquals(200, $response->getCode());

        $body = $response->getBody();
        $this->assertTrue(property_exists($body->headers, 'cookie'));
        $this->assertStringContainsString('foo=bar', $body->headers->cookie);
    }

    public function testTimeoutFail(): void
    {
        $this->expectException(Exception::class);
        Request::new()
            ->to('https://postman-echo.com/delay/3')
            ->timeout(1)
            ->send();
    }

    public function testDefaultHeaders(): void
    {
        $client = Request::new()
            ->withHeader('header1', 'Hello')
            ->withHeader('header2', 'world');

        $response = $client->to('https://postman-echo.com/get')->send();

        $this->assertEquals(200, $response->getCode());
        $body = $response->getBody();
        $this->assertTrue(property_exists($body->headers, 'header1'));
        $this->assertEquals('Hello', $body->headers->header1);
        $this->assertTrue(property_exists($body->headers, 'header2'));
        $this->assertEquals('world', $body->headers->header2);

        $response = Request::new()
            ->withHeader('header1', 'Custom value')
            ->to('https://postman-echo.com/get')
            ->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('Custom value', $response->getBody()->headers->header1);
    }

    public function testSetBearerToken(): void
    {
        $token = "eyJ0eXAiOiJKV1Qi...";
        $response = Request::new()
            ->to("https://postman-echo.com/get")
            ->withBearerToken($token)
            ->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertTrue(property_exists($response->getBody()->headers, 'authorization'));
        $this->assertEquals('Bearer ' . $token, $response->getBody()->headers->authorization);
    }

    public function testBasicAuthentication(): void
    {
        $response = Request::new()
            ->to('https://postman-echo.com/get')
            ->withCurlOption(CURLOPT_USERPWD, 'user:password')
            ->send();

        $this->assertEquals('Basic dXNlcjpwYXNzd29yZA==', $response->getBody()->headers->authorization);
    }

    public function testCustomHeaders(): void
    {
        $response = Request::new()
            ->to('https://postman-echo.com/get')
            ->withHeader('user-agent', 'niduxrest-php/3.0')
            ->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('niduxrest-php/3.0', $response->getBody()->headers->{'user-agent'});
    }

    // ==========================================
    // GET TESTS
    // ==========================================

    public function testGet(): void
    {
        $response = Request::new()
            ->to('https://postman-echo.com/get?name=Mark')
            ->withHeader('Accept', 'application/json')
            ->withQuery(['nick' => 'thefosk'])
            ->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('Mark', $response->getBody()->args->name);
        $this->assertEquals('thefosk', $response->getBody()->args->nick);
    }

    public function testGetHelper(): void
    {
        $response = Request::new()
            ->get('https://postman-echo.com/get?name=Mark')
            ->withHeader('Accept', 'application/json')
            ->withQuery(['nick' => 'thefosk'])
            ->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('Mark', $response->getBody()->args->name);
        $this->assertEquals('thefosk', $response->getBody()->args->nick);
    }

    public function testGetMultidimensionalArray(): void
    {
        $response = Request::new()
            ->to('https://postman-echo.com/get')
            ->withQuery([
                'key' => 'value',
                'items' => ['item1', 'item2'],
            ])
            ->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('value', $response->getBody()->args->key);
        $this->assertEquals('item1', $response->getBody()->args->items[0]);
        $this->assertEquals('item2', $response->getBody()->args->items[1]);
    }

    public function testGetWithDots(): void
    {
        $response = Request::new()
            ->to('https://postman-echo.com/get')
            ->withQuery([
                'user.name' => 'Mark',
                'nick' => 'thefosk',
            ])
            ->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('Mark', $response->getBody()->args->{'user.name'});
        $this->assertEquals('thefosk', $response->getBody()->args->nick);
    }

    // ==========================================
    // POST TESTS (FORM)
    // ==========================================

    public function testPostForm(): void
    {
        $response = Request::new()
            ->withMethod(Method::POST)
            ->to('https://postman-echo.com/post')
            ->withBody(['name' => 'Mark', 'nick' => 'thefosk'], false)
            ->send();

        $this->assertEquals(200, $response->getCode());
        $body = $response->getBody();
        $this->assertEquals('application/x-www-form-urlencoded', $body->headers->{'content-type'});
        $this->assertEquals('Mark', $body->form->name);
        $this->assertEquals('thefosk', $body->form->nick);
    }

    public function testPostArray(): void
    {
        $response = Request::new()
            ->withMethod(Method::POST)
            ->to('https://postman-echo.com/post')
            ->withBody(['name[0]' => 'Mark', 'name[1]' => 'John'], false)
            ->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('Mark', $response->getBody()->form->{'name[0]'});
        $this->assertEquals('John', $response->getBody()->form->{'name[1]'});
    }

    public function testRawPostJson(): void
    {
        $response = Request::new()
            ->withMethod(Method::POST)
            ->to('https://postman-echo.com/post')
            ->withBody(['author' => 'Sam Sullivan'], true) // true = json
            ->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('application/json', $response->getBody()->headers->{'content-type'});
        $this->assertEquals('Sam Sullivan', $response->getBody()->json->author ?? $response->getBody()->data->author);
    }

    // ==========================================
    // OTHER VERBS
    // ==========================================

    public function testPut(): void
    {
        $response = Request::new()
            ->withMethod(Method::PUT)
            ->to('https://postman-echo.com/put')
            ->withBody(['name' => 'Mark'], false)
            ->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('Mark', $response->getBody()->form->name);
    }

    public function testPatch(): void
    {
        $response = Request::new()
            ->withMethod(Method::PATCH)
            ->to('https://postman-echo.com/patch')
            ->withBody(['name' => 'Mark'], false)
            ->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('Mark', $response->getBody()->form->name);
    }

    public function testDelete(): void
    {
        $response = Request::new()
            ->withMethod(Method::DELETE)
            ->to('https://postman-echo.com/delete')
            ->withBody(['name' => 'Mark'], false)
            ->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertNotNull($response->getBody());
    }

    // ==========================================
    // UPLOAD / MULTIPART
    // ==========================================

    public function testUpload(): void
    {
        $fixture = __DIR__ . '/fixtures/upload.txt';

        $response = Request::new()
            ->withMethod(Method::POST)
            ->to('https://postman-echo.com/post')
            ->withMultipartBody([
                'name' => 'testFile',
                'file' => Body::prepareFile($fixture)
            ])
            ->send();

        $this->assertEquals(200, $response->getCode());

        $body = $response->getBody();
        $this->assertEquals('testFile', $body->form->name);
        $this->assertTrue(property_exists($body->files, 'upload.txt') || property_exists($body->files, 'file'));
    }
}
