<?php

namespace Http\Adapter\Guzzle5\Tests;

use GuzzleHttp\Exception as GuzzleExceptions;
use GuzzleHttp\Message\Request as GuzzleRequest;
use GuzzleHttp\Message\Response as GuzzleResponse;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Stream\Stream;
use Http\Adapter\Guzzle5\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ExceptionTest extends TestCase
{
    private $guzzleRequest;

    private $guzzleResponse;

    public function setUp()
    {
        $this->guzzleRequest = new GuzzleRequest('GET', 'http://foo.com');
        $this->guzzleResponse = new GuzzleResponse('400', [], Stream::factory('message body'), ['protocol_version' => '1,1']);
    }

    protected function makeRequest(GuzzleExceptions\TransferException $exception)
    {
        $client = $this->getMockBuilder('GuzzleHttp\ClientInterface')->getMock();
        $client->expects($this->any())->method('send')->willThrowException($exception);
        $client->expects($this->any())->method('createRequest')->willReturn($this->guzzleRequest);

        $request = new Psr7Request('GET', 'http://foo.com');
        (new Client($client, new GuzzleMessageFactory()))->sendRequest($request);
    }

    public function testConnectException()
    {
        // Guzzle's ConnectException should be converted to a NetworkException
        $this->expectException('Http\Client\Exception\NetworkException');
        $this->makeRequest(new GuzzleExceptions\ConnectException('foo', $this->guzzleRequest));
    }

    public function testTooManyRedirectsException()
    {
        // Guzzle's TooManyRedirectsException should be converted to a RequestException
        $this->expectException('Http\Client\Exception\RequestException');
        $this->makeRequest(new GuzzleExceptions\TooManyRedirectsException('foo', $this->guzzleRequest));
    }

    public function testRequestException()
    {
        // Guzzle's RequestException should be converted to a HttpException
        $this->expectException('Http\Client\Exception\HttpException');
        $this->makeRequest(new GuzzleExceptions\RequestException('foo', $this->guzzleRequest, $this->guzzleResponse));
    }

    public function testRequestExceptionWithoutResponse()
    {
        // Guzzle's RequestException with no response should be converted to a RequestException
        $this->expectException('Http\Client\Exception\RequestException');
        $this->makeRequest(new GuzzleExceptions\RequestException('foo', $this->guzzleRequest));
    }

    public function testBadResponseException()
    {
        // Guzzle's BadResponseException should be converted to a HttpException
        $this->expectException('Http\Client\Exception\HttpException');
        $this->makeRequest(new GuzzleExceptions\BadResponseException('foo', $this->guzzleRequest, $this->guzzleResponse));
    }

    public function testBadResponseExceptionWithoutResponse()
    {
        // Guzzle's BadResponseException with no response should be converted to a RequestException
        $this->expectException('Http\Client\Exception\RequestException');
        $this->makeRequest(new GuzzleExceptions\BadResponseException('foo', $this->guzzleRequest));
    }

    public function testClientException()
    {
        // Guzzle's ClientException should be converted to a HttpException
        $this->expectException('Http\Client\Exception\HttpException');
        $this->makeRequest(new GuzzleExceptions\ClientException('foo', $this->guzzleRequest, $this->guzzleResponse));
    }

    public function testClientExceptionWithoutResponse()
    {
        // Guzzle's ClientException with no response should be converted to a RequestException
        $this->expectException('Http\Client\Exception\RequestException');
        $this->makeRequest(new GuzzleExceptions\ClientException('foo', $this->guzzleRequest));
    }

    public function testServerException()
    {
        // Guzzle's ServerException should be converted to a HttpException
        $this->expectException('Http\Client\Exception\HttpException');
        $this->makeRequest(new GuzzleExceptions\ServerException('foo', $this->guzzleRequest, $this->guzzleResponse));
    }

    public function testServerExceptionWithoutResponse()
    {
        // Guzzle's ServerException with no response should be converted to a RequestException
        $this->expectException('Http\Client\Exception\RequestException');
        $this->makeRequest(new GuzzleExceptions\BadResponseException('foo', $this->guzzleRequest));
    }

    public function testTransferException()
    {
        // Guzzle's TransferException should be converted to a TransferException
        $this->expectException('Http\Client\Exception\TransferException');
        $this->makeRequest(new GuzzleExceptions\TransferException('foo'));
    }

    public function testParseException()
    {
        // Guzzle's ParseException should be converted to a TransferException
        $this->expectException('Http\Client\Exception\TransferException');
        $this->makeRequest(new GuzzleExceptions\ParseException('foo', $this->guzzleResponse));
    }
}
