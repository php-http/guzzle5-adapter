<?php

/*
 * This file is part of the Http Adapter package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Http\Adapter\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Ring\Client\CurlHandler;
use Http\Adapter\Guzzle5HttpAdapter;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class Guzzle5HttpAdapterTest extends HttpAdapterTest
{
    public function testGetName()
    {
        $this->assertSame('guzzle5', $this->httpAdapter->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function createHttpAdapter()
    {
        return new Guzzle5HttpAdapter(new Client(['handler' => $this->createHandler()]));
    }

    /**
     * Returns a handler for the client
     */
    protected function createHandler()
    {
        return new CurlHandler();
    }
}
