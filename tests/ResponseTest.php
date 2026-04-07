<?php

namespace Nidux\Rest\Tests;

use Nidux\Rest\Request;
use Nidux\Rest\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testJSONObjects(): void
    {
        $response = new Response(200, '{"a":1,"b":2,"c":3,"d":4,"e":5}', '');
        $this->assertEquals(1, $response->getBody()->a);
    }

    public function testJSONAssociativeArrays(): void
    {
        $response = new Response(200, '{"a":1,"b":2,"c":3,"d":4,"e":5}', '');
        $this->assertEquals(1, $response->getArray()['a']);
    }

    public function testHeaderResponse(): void
    {
        $response = Request::new()
            ->to("https://postman-echo.com/get")
            ->send();

        $headers = $response->getHeaders();

        $this->assertNotEmpty($headers);
        $this->assertIsArray($headers);
        $this->assertEquals('application/json; charset=utf-8', $headers['content-type']);
    }

    public function testStatusHelpers(): void
    {
        $response200 = new Response(200, '', '');
        $this->assertTrue($response200->isSuccessful());
        $this->assertFalse($response200->isClientError());
        $this->assertFalse($response200->isServerError());

        $response404 = new Response(404, '', '');
        $this->assertTrue($response404->isClientError());
        $this->assertFalse($response404->isSuccessful());

        $response500 = new Response(500, '', '');
        $this->assertTrue($response500->isServerError());
    }
}