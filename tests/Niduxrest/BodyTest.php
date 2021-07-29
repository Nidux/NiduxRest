<?php

namespace Tests;

use CURLFile;
use Niduxrest\Request as Request;
use Niduxrest\Request\Body as Body;
use PHPUnit\Framework\TestCase;

class BodyTest extends TestCase
{
    public function testCURLFile()
    {
        $fixture = __DIR__ . '/fixtures/upload.txt';

        $file = Body::File($fixture);

        if (PHP_MAJOR_VERSION === 5 && PHP_MINOR_VERSION === 4) {
            $this->assertEquals($file, sprintf('@%s;filename=%s;type=', $fixture, basename($fixture)));
        } else {
            $this->assertTrue($file instanceof CURLFile);
        }
    }

    public function testHttpBuildQueryWithCurlFile()
    {
        $fixture = __DIR__ . '/fixtures/upload.txt';

        $file = Body::File($fixture);
        $body = [
            'to' => 'mail@mailinator.com',
            'from' => 'mail@mailinator.com',
            'file' => $file,
        ];

        $result = Request::buildHTTPCurlQuery($body);
        $this->assertEquals($result['file'], $file);
    }

    public function testJson()
    {
        $body = Body::Json(['foo', 'bar']);

        $this->assertEquals('["foo","bar"]', $body);
    }

    public function testForm()
    {
        $body = Body::Form(['foo' => 'bar', 'bar' => 'baz']);

        $this->assertEquals('foo=bar&bar=baz', $body);

        // try again with a string
        $body = Body::Form($body);

        $this->assertEquals('foo=bar&bar=baz', $body);
    }

    public function testMultipart()
    {
        $arr = ['foo' => 'bar', 'bar' => 'baz'];

        $body = Body::Multipart((object)$arr);

        $this->assertEquals($body, $arr);

        $body = Body::Multipart('flat');

        $this->assertEquals(['flat'], $body);
    }

    public function testMultipartFiles()
    {
        $fixture = __DIR__ . '/fixtures/upload.txt';

        $data = ['foo' => 'bar', 'bar' => 'baz'];
        $files = ['test' => $fixture];

        $body = Body::Multipart($data, $files);

        // echo $body;

        $this->assertEquals($body, [
            'foo' => 'bar',
            'bar' => 'baz',
            'test' => Body::File($fixture),
        ]);
    }
}
