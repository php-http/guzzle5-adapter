<?php

/*
 * This file is part of the Http Adapter package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Http\Adapter\Guzzle5\Tests;

use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle5\Client;
use Http\Client\Tests\HttpClientTest;

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
        return new Client(new GuzzleClient(['handler' => $this->createHandler()]));
    }

    /**
     * Returns a handler for the client
     *
     * @return object
     */
    abstract protected function createHandler();
}
