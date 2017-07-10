<?php

namespace Http\Adapter\Guzzle5\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle5\Client;
use Http\Client\Tests\HttpClientTest;
use Http\Message\MessageFactory\GuzzleMessageFactory;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
abstract class HttpAdapterTest extends HttpClientTest
{
    /**
     * {@inheritdoc}
     */
    protected function createHttpAdapter()
    {
        return new Client(
            new GuzzleClient(['handler' => $this->createHandler()]),
            new GuzzleMessageFactory()
        );
    }

    /**
     * Returns a handler for the client.
     *
     * @return object
     */
    abstract protected function createHandler();

    /**
     * @group integration
     * @group network
     */
    public function testHeadRequestShouldEnd()
    {
        // It works with the PHP Built-in web server via $request = new Request('GET', $this->getUri());
        $request = new Request('HEAD', 'https://httpbin.org/');
        $this->httpAdapter->sendRequest($request);
        $this->addToAssertionCount(1);
    }
}
