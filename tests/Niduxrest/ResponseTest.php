<?php

namespace Tests;

use Niduxrest\Request as Request;
use Niduxrest\Response as Response;
use PHPUnit\Framework\TestCase;


class ResponseTest extends TestCase
{
    public function testJSONAssociativeArrays()
    {
        $opts = Request::jsonOpts(true);
        $response = new Response(200, '{"a":1,"b":2,"c":3,"d":4,"e":5}', '', $opts);

        $this->assertEquals(1, $response->body['a']);
    }

    public function testJSONAObjects()
    {
        $opts = Request::jsonOpts(false);
        $response = new Response(200, '{"a":1,"b":2,"c":3,"d":4,"e":5}', '', $opts);

        $this->assertEquals(1, $response->body->a);
    }

    public function testJSONOpts()
    {
        $opts = Request::jsonOpts(false, 512, JSON_NUMERIC_CHECK);
        $response = new Response(200, '{"number": 1234567890}', '', $opts);

        $this->assertSame($response->body->number, 1234567890);
    }
}
