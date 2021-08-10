<?php

namespace Tests;

use Niduxrest\Exception;
use Niduxrest\Request as Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    // Generic
    public function testCurlOpts()
    {
        Request::curlOpt(CURLOPT_COOKIE, 'foo=bar');

        $response = Request::get('http://mockbin.com/request');

        $this->assertTrue(property_exists($response->body->cookies, 'foo'));

        Request::clearCurlOpts();
    }

    public function testTimeoutFail()
    {
        $this->expectException(Exception::class);
        Request::timeout(1);

        Request::get('http://mockbin.com/delay/1000');

        Request::timeout(null); // Cleaning timeout for the other tests
    }

    public function testDefaultHeaders()
    {
        $defaultHeaders = [
            'header1' => 'Hello',
            'header2' => 'world',
        ];
        Request::defaultHeaders($defaultHeaders);

        $response = Request::get('http://mockbin.com/request');

        $this->assertEquals(200, $response->code);
        $this->assertObjectHasAttribute('header1', $response->body->headers);
        $this->assertEquals('Hello', $response->body->headers->header1);
        $this->assertObjectHasAttribute('header2', $response->body->headers);
        $this->assertEquals('world', $response->body->headers->header2);

        $response = Request::get('http://mockbin.com/request', ['header1' => 'Custom value']);

        $this->assertEquals(200, $response->code);
        $this->assertObjectHasAttribute('header1', $response->body->headers);
        $this->assertEquals('Custom value', $response->body->headers->header1);

        Request::clearDefaultHeaders();

        $response = Request::get('http://mockbin.com/request');

        $this->assertEquals(200, $response->code);
        $this->assertObjectNotHasAttribute('header1', $response->body->headers);
        $this->assertObjectNotHasAttribute('header2', $response->body->headers);
    }

    public function testDefaultHeader()
    {
        Request::defaultHeader('Hello', 'custom');

        $response = Request::get('http://mockbin.com/request');

        $this->assertEquals(200, $response->code);
        $this->assertTrue(property_exists($response->body->headers, 'hello'));
        $this->assertEquals('custom', $response->body->headers->hello);

        Request::clearDefaultHeaders();

        $response = Request::get('http://mockbin.com/request');

        $this->assertEquals(200, $response->code);
        $this->assertFalse(property_exists($response->body->headers, 'hello'));
    }

    public function testSetBearerToken()
    {
        $exampleBearerToken = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczpcL1wvdWlhcGkubmlkdXgubmV0XC8xMDAwOFwvbG9naW4iLCJpYXQiOjE2Mjc1Njk1MjYsImV4cCI6MTYyNzU5MTEyNiwibmJmIjoxNjI3NTY5NTI2LCJqdGkiOiJxSmh3amJGSllEV2tuMzNqIiwic3ViIjoxLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.6bdg7bI6sSj_Urc7rQ66ZNG975Abl8SKJZv_n4EtJ_U";
        Request::setBearerToken($exampleBearerToken);
        $response = Request::get("https://enk7njbsi58p1xd.m.pipedream.net");
        $this->assertEquals(200, $response->code);
        $this->assertTrue(property_exists($response->body->headers, 'authorization'));
        $this->assertEquals('Bearer ' . $exampleBearerToken, $response->body->headers->{'authorization'});
    }


    public function testSetMashapeKey()
    {
        Request::setMashapeKey('abcd');

        $response = Request::get('http://mockbin.com/request');

        $this->assertEquals(200, $response->code);
        $this->assertTrue(property_exists($response->body->headers, 'x-mashape-key'));
        $this->assertEquals('abcd', $response->body->headers->{'x-mashape-key'});

        // send another request
        $response = Request::get('http://mockbin.com/request');

        $this->assertEquals(200, $response->code);
        $this->assertTrue(property_exists($response->body->headers, 'x-mashape-key'));
        $this->assertEquals('abcd', $response->body->headers->{'x-mashape-key'});

        Request::clearDefaultHeaders();

        $response = Request::get('http://mockbin.com/request');

        $this->assertEquals(200, $response->code);
        $this->assertFalse(property_exists($response->body->headers, 'x-mashape-key'));
    }

    public function testGzip()
    {
        $response = Request::get('http://mockbin.com/gzip/request', ["accept-encoding" => "gzip"]);
        $this->assertEquals('gzip', $response->headers['content-encoding'] ?? $response->headers['Content-Encoding']);
    }

    public function testBasicAuthenticationDeprecated()
    {

        $response = Request::get('http://mockbin.com/request', [], [], 'user', 'password');

        $this->assertEquals('Basic dXNlcjpwYXNzd29yZA==', $response->body->headers->authorization);
    }

    public function testBasicAuthentication()
    {
        Request::auth('user', 'password');

        $response = Request::get('http://mockbin.com/request');

        $this->assertEquals('Basic dXNlcjpwYXNzd29yZA==', $response->body->headers->authorization);
    }

    public function testCustomHeaders()
    {
        $response = Request::get('http://mockbin.com/request', [
            'user-agent' => 'unirest-php',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('unirest-php', $response->body->headers->{'user-agent'});
    }

    // GET
    public function testGet()
    {
        $response = Request::get('http://mockbin.com/request?name=Mark', [
            'Accept' => 'application/json',
        ], [
            'nick' => 'thefosk',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Mark', $response->body->queryString->name);
        $this->assertEquals('thefosk', $response->body->queryString->nick);
    }

    public function testGetWithExplicitPort()
    {
        $response = Request::get('http://mockbin.com:80/request?name=Mark', [
            'Accept' => 'application/json',
        ], [
            'nick' => 'thefosk',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Mark', $response->body->queryString->name);
        $this->assertEquals('thefosk', $response->body->queryString->nick);
    }

    public function testGetMultidimensionalArray()
    {
        $response = Request::get('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'key' => 'value',
            'items' => [
                'item1',
                'item2',
            ],
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('value', $response->body->queryString->key);
        $this->assertEquals('item1', $response->body->queryString->items[0]);
        $this->assertEquals('item2', $response->body->queryString->items[1]);
    }

    public function testGetWithDots()
    {
        $response = Request::get('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'user.name' => 'Mark',
            'nick' => 'thefosk',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Mark', $response->body->queryString->{'user.name'});
        $this->assertEquals('thefosk', $response->body->queryString->nick);
    }

    public function testGetWithDotsAlt()
    {
        $response = Request::get('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'user.name' => 'Mark Bond',
            'nick' => 'thefosk',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Mark Bond', $response->body->queryString->{'user.name'});
        $this->assertEquals('thefosk', $response->body->queryString->nick);
    }

    public function testGetWithEqualSign()
    {
        $response = Request::get('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'name' => 'Mark=Hello',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Mark=Hello', $response->body->queryString->name);
    }

    public function testGetWithEqualSignAlt()
    {
        $response = Request::get('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'name' => 'Mark=Hello=John',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Mark=Hello=John', $response->body->queryString->name);
    }

    public function testGetWithComplexQuery()
    {
        $response = Request::get('http://mockbin.com/request?query=[{"type":"/music/album","name":null,"artist":{"id":"/en/bob_dylan"},"limit":3}]&cursor');

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('', $response->body->queryString->cursor);
        $this->assertEquals('[{"type":"/music/album","name":null,"artist":{"id":"/en/bob_dylan"},"limit":3}]', $response->body->queryString->query);
    }

    public function testGetArray()
    {
        $response = Request::get('http://mockbin.com/request', [], [
            'name[0]' => 'Mark',
            'name[1]' => 'John',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Mark', $response->body->queryString->name[0]);
        $this->assertEquals('John', $response->body->queryString->name[1]);
    }

    // HEAD
    public function testHead()
    {
        $response = Request::head('http://mockbin.com/request?name=Mark', [
            'Accept' => 'application/json',
        ]);

        $this->assertEquals(200, $response->code);
    }

    // POST
    public function testPost()
    {
        $response = Request::post('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'name' => 'Mark',
            'nick' => 'thefosk',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Mark', $response->body->postData->params->name);
        $this->assertEquals('thefosk', $response->body->postData->params->nick);
    }


    // POST
    public function testPostWithExplicitPortNumber()
    {
        $response = Request::post('http://mockbin.com:80/request', [
            'Accept' => 'application/json',
        ], [
            'name' => 'Mark',
            'nick' => 'thefosk',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Mark', $response->body->postData->params->name);
        $this->assertEquals('thefosk', $response->body->postData->params->nick);
    }

    public function testPostForm()
    {
        $body = Request\Body::Form([
            'name' => 'Mark',
            'nick' => 'thefosk',
        ]);

        $response = Request::post('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], $body);

        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('application/x-www-form-urlencoded', $response->body->headers->{'content-type'});
        $this->assertEquals('application/x-www-form-urlencoded', $response->body->postData->mimeType);
        $this->assertEquals('Mark', $response->body->postData->params->name);
        $this->assertEquals('thefosk', $response->body->postData->params->nick);
    }

    public function testPostMultipart()
    {
        $body = Request\Body::Multipart([
            'name' => 'Mark',
            'nick' => 'thefosk',
        ]);

        $response = Request::post('http://mockbin.com/request', (object)[
            'Accept' => 'application/json',
        ], $body);

        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('multipart/form-data', explode(';', $response->body->headers->{'content-type'})[0]);
        $this->assertEquals('multipart/form-data', $response->body->postData->mimeType);
        $this->assertEquals('Mark', $response->body->postData->params->name);
        $this->assertEquals('thefosk', $response->body->postData->params->nick);
    }

    public function testPostWithEqualSign()
    {
        $body = Request\Body::Form([
            'name' => 'Mark=Hello',
        ]);

        $response = Request::post('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Mark=Hello', $response->body->postData->params->name);
    }

    public function testPostArray()
    {
        $response = Request::post('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'name[0]' => 'Mark',
            'name[1]' => 'John',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Mark', $response->body->postData->params->{'name[0]'});
        $this->assertEquals('John', $response->body->postData->params->{'name[1]'});
    }

    public function testPostWithDots()
    {
        $response = Request::post('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'user.name' => 'Mark',
            'nick' => 'thefosk',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Mark', $response->body->postData->params->{'user.name'});
        $this->assertEquals('thefosk', $response->body->postData->params->nick);
    }

    public function testRawPost()
    {
        $response = Request::post('http://mockbin.com/request', [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], json_encode([
            'author' => 'Sam Sullivan',
        ]));

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Sam Sullivan', json_decode($response->body->postData->text)->author);
    }

    public function testPostMultidimensionalArray()
    {
        $body = Request\Body::Form([
            'key' => 'value',
            'items' => [
                'item1',
                'item2',
            ],
        ]);

        $response = Request::post('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('value', $response->body->postData->params->key);
        $this->assertEquals('item1', $response->body->postData->params->{'items[0]'});
        $this->assertEquals('item2', $response->body->postData->params->{'items[1]'});
    }

    // PUT
    public function testPut()
    {
        $response = Request::put('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'name' => 'Mark',
            'nick' => 'thefosk',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('PUT', $response->body->method);
        $this->assertEquals('Mark', $response->body->postData->params->name);
        $this->assertEquals('thefosk', $response->body->postData->params->nick);
    }

    // PATCH
    public function testPatch()
    {
        $response = Request::patch('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'name' => 'Mark',
            'nick' => 'thefosk',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('PATCH', $response->body->method);
        $this->assertEquals('Mark', $response->body->postData->params->name);
        $this->assertEquals('thefosk', $response->body->postData->params->nick);
    }

    // DELETE
    public function testDelete()
    {
        $response = Request::delete('http://mockbin.com/request', [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], [
            'name' => 'Mark',
            'nick' => 'thefosk',
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('DELETE', $response->body->method);
    }

    // Upload
    public function testUpload()
    {
        $fixture = __DIR__ . '/../fixtures/upload.txt';

        $headers = ['Accept' => 'application/json'];
        $files = ['file' => $fixture];
        $data = ['name' => 'ahmad'];

        $body = Request\Body::Multipart($data, $files);

        $response = Request::post('http://mockbin.com/request', $headers, $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('ahmad', $response->body->postData->params->name);
        $this->assertEquals('This is a test', $response->body->postData->params->file);
    }

    public function testNamedURLWithPort()
    {

        $response = Request::post('https://www.facturar.cr:1391', [
            'Accept' => 'application/json',
        ]);

        $this->assertEquals(200, $response->code);
        print_r($response);
    }

    public function testUploadWithoutHelper()
    {
        $fixture = __DIR__ . '/../fixtures/upload.txt';

        $response = Request::post('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'name' => 'Mark',
            'file' => Request\Body::File($fixture),
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Mark', $response->body->postData->params->name);
        $this->assertEquals('This is a test', $response->body->postData->params->file);
    }

    public function testUploadIfFilePartOfData()
    {
        $fixture = __DIR__ . '/../fixtures/upload.txt';

        $response = Request::post('http://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'name' => 'Mark',
            'files[owl.gif]' => Request\Body::File($fixture),
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Mark', $response->body->postData->params->name);
        $this->assertEquals('This is a test', $response->body->postData->params->{'files[owl.gif]'});
    }
}
