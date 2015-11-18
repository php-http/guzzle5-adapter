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
use Http\Adapter\Guzzle5HttpAdapter;
use Http\Client\Tests\HttpClientTest;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
abstract class Guzzle5HttpAdapterTest extends HttpClientTest
{
    /**
     * {@inheritdoc}
     */
    protected function createHttpAdapter()
    {
        return new Guzzle5HttpAdapter(new Client(['handler' => $this->createHandler()]));
    }

    /**
     * Returns a handler for the client
     *
     * @return object
     */
    abstract protected function createHandler();
}
