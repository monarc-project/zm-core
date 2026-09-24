<?php declare(strict_types=1);

namespace Monarc\Core\Tests\Unit\Request\Psr7Bridge;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\Uri;
use Monarc\Core\Request\Psr7Bridge\RequestConverter;
use PHPUnit\Framework\TestCase;

/** @covers \Monarc\Core\Request\Psr7Bridge\RequestConverter */
final class RequestConverterTest extends TestCase
{
    public function testPreservesRawJsonBodyForRestfulControllerDispatch(): void
    {
        $stream = new Stream('php://temp', 'rw');
        $stream->write('{"languageCode":"en"}');
        $stream->rewind();
        $request = new ServerRequest(
            [],
            [],
            new Uri('http://example.test/api/scenario/v1/anrs/1/analysis'),
            'POST',
            $stream,
            ['Content-Type' => 'application/json']
        );

        $converted = RequestConverter::toLaminas($request);

        $this->assertSame('{"languageCode":"en"}', $converted->getContent());
    }
}
