<?php

/*
 * This file is part of the Http Adapter package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Http\Adapter;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\RequestInterface as GuzzleRequest;
use GuzzleHttp\Message\ResponseInterface as GuzzleResponse;
use GuzzleHttp\Pool;
use Http\Adapter\Common\Exception\HttpAdapterException;
use Http\Adapter\Common\Exception\MultiHttpAdapterException;
use Http\Common\Message\MessageFactoryGuesser;
use Http\Message\MessageFactory;
use Http\Message\MessageFactoryAware;
use Http\Message\MessageFactoryAwareTemplate;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class Guzzle5HttpAdapter implements HttpAdapter, MessageFactoryAware
{
    use MessageFactoryAwareTemplate;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     *
     * @param ClientInterface|null $client
     * @param MessageFactory|null  $messageFactory
     */
    public function __construct(ClientInterface $client = null, MessageFactory $messageFactory = null)
    {
        $this->client = $client ?: new Client();
        $this->messageFactory = $messageFactory ?: MessageFactoryGuesser::guess();

    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request, array $options = [])
    {
        $guzzleRequest = $this->createRequest($request, $options);

        try {
            $response = $this->client->send($guzzleRequest);
        } catch (RequestException $e) {
            throw $this->createException($e, $request);
        }

        return $this->createResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequests(array $requests, array $options = [])
    {
        $requests = array_values($requests);
        $guzzleRequests = [];

        foreach ($requests as $request) {
            $guzzleRequests[] = $this->createRequest($request, $options);
        }

        $results = Pool::batch($this->client, $guzzleRequests);

        $exceptions = [];
        $responses = [];

        foreach ($guzzleRequests as $key => $guzzleRequest) {
            $result = $results->getResult($guzzleRequest);

            if ($result instanceof GuzzleResponse) {
                $responses[] = $this->createResponse($result);
            } elseif ($result instanceof RequestException) {
                $exceptions[] = $this->createException($result, $requests[$key]);
            }
        }

        if (count($exceptions) > 0) {
            throw new MultiHttpAdapterException($exceptions, $responses);
        }

        return $responses;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'guzzle5';
    }

    /**
     * Converts a PSR request into a Guzzle request
     *
     * @param RequestInterface $request
     *
     * @return GuzzleRequest
     */
    private function createRequest(RequestInterface $request, array $options = [])
    {
        $options = $this->buildOptions($options);

        $options['version'] = $request->getProtocolVersion();
        $options['headers'] = $request->getHeaders();
        $options['body']    = (string) $request->getBody();

        return $this->client->createRequest(
            $request->getMethod(),
            (string) $request->getUri(),
            $options
        );
    }

    /**
     * Converts a Guzzle response into a PSR response
     *
     * @param GuzzleResponse $response
     *
     * @return ResponseInterface
     */
    private function createResponse(GuzzleResponse $response)
    {
        $body = $response->getBody();

        return $this->getMessageFactory()->createResponse(
            $response->getStatusCode(),
            null,
            $response->getProtocolVersion(),
            $response->getHeaders(),
            isset($body) ? $body->detach() : null
        );
    }

    /**
     * Converts a Guzzle exception into an HttpAdapter exception
     *
     * @param RequestException $exception
     *
     * @return HttpAdapterException
     */
    private function createException(
        RequestException $exception,
        RequestInterface $originalRequest
    ) {
        $adapterException = new HttpAdapterException(
            $exception->getMessage(),
            0,
            $exception
        );

        $response = null;

        if ($exception->hasResponse()) {
            $response = $this->createResponse($exception->getResponse());
        }

        $adapterException->setResponse($response);
        $adapterException->setRequest($originalRequest);

        return $adapterException;
    }

    /**
     * Builds options for Guzzle
     *
     * @param array $options
     *
     * @return array
     */
    private function buildOptions(array $options)
    {
        $guzzleOptions = [
            'exceptions'      => false,
            'allow_redirects' => false,
        ];

        if (isset($options['timeout'])) {
            $guzzleOptions['connect_timeout'] = $options['timeout'];
            $guzzleOptions['timeout'] = $options['timeout'];
        }

        return $guzzleOptions;
    }
}
