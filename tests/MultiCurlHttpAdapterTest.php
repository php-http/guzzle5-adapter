<?php

namespace Http\Adapter\Guzzle5\Tests;

use GuzzleHttp\Ring\Client\CurlMultiHandler;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class MultiCurlHttpAdapterTest extends HttpAdapterTest
{
    /**
     * {@inheritdoc}
     */
    protected function createHandler()
    {
        return new CurlMultiHandler();
    }
}
