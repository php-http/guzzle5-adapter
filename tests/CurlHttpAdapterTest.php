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

use GuzzleHttp\Ring\Client\CurlHandler;

/**
 * @requires PHP 5.5
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class CurlHttpAdapterTest extends HttpAdapterTest
{
    /**
     * {@inheritdoc}
     */
    protected function createHandler()
    {
        return new CurlHandler();
    }
}
