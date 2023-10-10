<?php

namespace Tests;

use Niduxrest\Request as Request;
use Niduxrest\Response as Response;
use PHPUnit\Framework\TestCase;


class ResponseTest extends TestCase
{
    public function testJSONAssociativeArrays()
    {
        Request::setJsonOpts(true);
        $response = new Response(200, '{"a":1,"b":2,"c":3,"d":4,"e":5}', '');

        $this->assertEquals(1, $response->getBody()['a']);
    }

    public function testHeaderResponse()
    {
        $response = Request::get("https://enk7njbsi58p1xd.m.pipedream.net");
        $headers = $response->getHeaders();

        $this->assertNotEmpty($headers);
        $this->assertIsArray($headers);
        $this->assertEquals('application/json; charset=utf-8', $headers['content-type']);
    }

    public function testJSONAObjects()
    {
        Request::setJsonOpts(false);
        $response = new Response(200, '{"a":1,"b":2,"c":3,"d":4,"e":5}', '');

        $this->assertEquals(1, $response->getBody()->a);
    }

    public function testJSONOpts()
    {
        Request::setJsonOpts(false, 512, JSON_NUMERIC_CHECK);
        $response = new Response(200, '{"number": 1234567890}', '');

        $this->assertSame($response->getBody()->number, 1234567890);
    }
}
