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
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Pool;
use Http\Adapter\Common\Exception\CannotFetchUri;
use Http\Adapter\Internal\Message\InternalRequest;
use Http\Adapter\Internal\Message\MessageFactory;
use Http\Adapter\Normalizer\BodyNormalizer;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class Guzzle5HttpAdapter extends Core\CurlHttpAdapter
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     *
     * @param ClientInterface|null $client
     * @param array                $options
     * @param MessageFactory|null  $messageFactory
     */
    public function __construct(ClientInterface $client = null, array $options = [], MessageFactory $messageFactory = null)
    {
        parent::__construct($options, $messageFactory);

        $this->client = $client ?: new Client();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'guzzle5';
    }

    /**
     * {@inheritdoc}
     */
    protected function sendInternalRequest(InternalRequest $internalRequest)
    {
        try {
            $response = $this->client->send($this->createRequest($internalRequest));
        } catch (RequestException $e) {
            throw new CannotFetchUri($e->getRequest()->getUrl(), $this->getName(), $e);
        }

        return $this->getMessageFactory()->createResponse(
            (integer) $response->getStatusCode(),
            null,
            $response->getProtocolVersion(),
            $response->getHeaders(),
            BodyNormalizer::normalize(
                function () use ($response) {
                    return $response->getBody()->detach();
                },
                $internalRequest->getMethod()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function sendInternalRequests(array $internalRequests, callable $success, callable $error)
    {
        $requests = [];
        foreach ($internalRequests as $internalRequest) {
            $requests[] = $this->createRequest($internalRequest, $success, $error);
        }

        Pool::batch($this->client, $requests);
    }

    /**
     * {@inheritdoc}
     */
    protected function createFile($file)
    {
        return fopen($file, 'r');
    }

    /**
     * Creates a request
     *
     * @param InternalRequest $internalRequest
     * @param callable|null   $success
     * @param callable|null   $error
     *
     * @return RequestInterface
     */
    private function createRequest(InternalRequest $internalRequest, callable $success = null, callable $error = null)
    {
        $request = $this->client->createRequest(
            $internalRequest->getMethod(),
            (string) $internalRequest->getUri(),
            [
                'exceptions'      => false,
                'allow_redirects' => false,
                'timeout'         => $this->getContextOption('timeout', $internalRequest),
                'connect_timeout' => $this->getContextOption('timeout', $internalRequest),
                'version'         => $internalRequest->getProtocolVersion(),
                'headers'         => $this->prepareHeaders($internalRequest),
                'body'            => $this->prepareContent($internalRequest),
            ]
        );

        if (isset($success)) {
            $messageFactory = $this->getMessageFactory();

            $request->getEmitter()->on(
                'complete',
                function (CompleteEvent $event) use ($success, $internalRequest, $messageFactory) {
                    $response = $messageFactory->createResponse(
                        (integer) $event->getResponse()->getStatusCode(),
                        null,
                        $event->getResponse()->getProtocolVersion(),
                        $event->getResponse()->getHeaders(),
                        BodyNormalizer::normalize(
                            function () use ($event) {
                                return $event->getResponse()->getBody()->detach();
                            },
                            $internalRequest->getMethod()
                        )
                    );

                    $response = new Core\Message\ParameterableResponse($response);
                    $response = $response->withParameter('request', $internalRequest);
                    call_user_func($success, $response);
                }
            );
        }

        if (isset($error)) {
            $httpAdapterName = $this->getName();

            $request->getEmitter()->on(
                'error',
                function (ErrorEvent $event) use ($error, $internalRequest, $httpAdapterName) {
                    $exception = new CannotFetchUri(
                        $event->getException()->getRequest()->getUrl(),
                        $httpAdapterName,
                        $event->getException()
                    );
                    $exception->setRequest($internalRequest);
                    call_user_func($error, $exception);
                }
            );
        }

        return $request;
    }
}
