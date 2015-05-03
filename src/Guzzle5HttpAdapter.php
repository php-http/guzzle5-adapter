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
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Pool;
use Http\Adapter\Message\InternalRequestInterface;
use Http\Adapter\Normalizer\BodyNormalizer;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class Guzzle5HttpAdapter extends CurlHttpAdapter
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     *
     * @param ClientInterface|null        $client
     * @param ConfigurationInterface|null $configuration
     */
    public function __construct(ClientInterface $client = null, ConfigurationInterface $configuration = null)
    {
        parent::__construct($configuration);

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
    protected function sendInternalRequest(InternalRequestInterface $internalRequest)
    {
        try {
            $response = $this->client->send($this->createRequest($internalRequest));
        } catch (\Exception $e) {
            var_dump($e); exit;
            throw HttpAdapterException::cannotFetchUri(
                $e->getRequest()->getUrl(),
                $this->getName(),
                $e->getMessage()
            );
        }

        return $this->getConfiguration()->getMessageFactory()->createResponse(
            (integer) $response->getStatusCode(),
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
    protected function sendInternalRequests(array $internalRequests, $success, $error)
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
     * Creates a request.
     *
     * @param InternalRequestInterface $internalRequest
     * @param callable|null            $success
     * @param callable|null            $error
     *
     * @return RequestInterface
     */
    private function createRequest(InternalRequestInterface $internalRequest, callable $success = null, callable $error = null)
    {
        $request = $this->client->createRequest(
            $internalRequest->getMethod(),
            (string) $internalRequest->getUri(),
            [
                'exceptions'      => false,
                'allow_redirects' => false,
                'timeout'         => $this->getConfiguration()->getTimeout(),
                'connect_timeout' => $this->getConfiguration()->getTimeout(),
                'version'         => $internalRequest->getProtocolVersion(),
                'headers'         => $this->prepareHeaders($internalRequest),
                'body'            => $this->prepareContent($internalRequest),
            ]
        );

        if (isset($success)) {
            $messageFactory = $this->getConfiguration()->getMessageFactory();

            $request->getEmitter()->on(
                'complete',
                function (CompleteEvent $event) use ($success, $internalRequest, $messageFactory) {
                    $response = $messageFactory->createResponse(
                        (integer) $event->getResponse()->getStatusCode(),
                        $event->getResponse()->getProtocolVersion(),
                        $event->getResponse()->getHeaders(),
                        BodyNormalizer::normalize(
                            function () use ($event) {
                                return $event->getResponse()->getBody()->detach();
                            },
                            $internalRequest->getMethod()
                        )
                    );

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
                    $exception = HttpAdapterException::cannotFetchUri(
                        $event->getException()->getRequest()->getUrl(),
                        $httpAdapterName,
                        $event->getException()->getMessage()
                    );
                    $exception->setRequest($internalRequest);
                    call_user_func($error, $exception);
                }
            );
        }

        return $request;
    }
}
