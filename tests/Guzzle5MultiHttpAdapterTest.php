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

use GuzzleHttp\Ring\Client\CurlMultiHandler;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class Guzzle5MultiHttpAdapterTest extends Guzzle5HttpAdapterTest
{
    /**
     * Returns a handler for the client
     */
    protected function createHandler()
    {
        return new CurlMultiHandler();
    }
}
