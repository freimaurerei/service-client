<?php

namespace Freimaurerei\ServiceClient;

use Freimaurerei\ServiceClient\Exception\LogicalException;
use Freimaurerei\ServiceClient\Exception\TransportException;
use Freimaurerei\ServiceClient\Logger\RestClientPsrLoggerDecorator;
use Freimaurerei\ServiceClient\Plugin\GuzzleRestServiceClientTimerPlugin;
use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Message\Response;
use Psr\Log\LoggerInterface;
use Freimaurerei\ServiceModel\Converter\Converter;
use Freimaurerei\ServiceModel\Converter\UriParamsConverter;

class RestClient extends AbstractClient
{
    const URI_PARAMS_SEPARATOR = '?';

    const CONTENT_TYPE = 'Content-Type';
    const ACCEPT = 'Accept';

    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_XML = 'application/xml';

    const TYPE_JSON = 'json';
    const TYPE_XML  = 'xml';

    /**
     * @var UriParamsConverter
     */
    private $uriConverter;

    /**
     * @param $type
     * @return Converter
     */
    protected static function getConverter($type)
    {
        $path = explode('/', $type);
        return Converter::factory(array_pop($path));
    }

    /**
     * @return GuzzleClient
     */
    protected function getFinalClientTransport()
    {
        return new GuzzleClient($this->getBaseUrl());
    }

    /**
     * @return null
     */
    protected function getFinalClientTimerPlugin()
    {
        return null;
    }

    /**
     * @param LoggerInterface $logger
     * @return RestClientPsrLoggerDecorator
     */
    protected function getFinalClientLoggerDecorator(LoggerInterface $logger)
    {
        return new RestClientPsrLoggerDecorator(
            static::getServiceName(),
            $logger
        );
    }

    /**
     * @param Model $request
     * @param string $uri
     * @param string $method (e.g. GET, POST, PUT, DELETE, PATCH, etc.)
     * @param string $type
     * @param string[] $headers
     * @param string $contentType
     * @param bool $runValidation
     *
     * @throws Exception\TransportException
     * @throws Exception\LogicalException
     * @return mixed
     */
    public function call(
        Model $request,
        $uri = null,
        $method = 'GET',
        $type = null,
        $headers = [],
        $contentType = self::CONTENT_TYPE_JSON,
        $runValidation = null
    ) {
        $this->setRunValidation($runValidation);

        try {
            $response = $this->sendRequest(
                $request,
                $uri,
                $method,
                array_merge($headers, [self::CONTENT_TYPE => $contentType, self::ACCEPT => $contentType])
            );

            $this->restoreRunValidation();

            if ($response->isError()) {
                throw new LogicalException($response->getMessage(), $response->getStatusCode());
            }

            return $this->processResponse($response, $type, $contentType);
        } catch (GuzzleException $e) {
            $this->restoreRunValidation();
            throw new TransportException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return \Freimaurerei\ServiceModel\Converter\Converter|UriParamsConverter
     */
    protected function getUriConverter()
    {
        if (is_null($this->uriConverter)) {
            $this->uriConverter = new UriParamsConverter();
        }

        return $this->uriConverter;
    }

    /**
     * @param string $uri
     * @param string $method (e.g. GET, POST, PUT, DELETE, PATCH, etc.)
     * @param Model $request
     * @param string[] $headers
     * @return \Guzzle\Http\Message\Response
     */
    protected function sendRequest(Model $request, $uri = null, $method = 'GET', $headers = null)
    {
        $request = $this->request($request, $uri, $method, $headers);

        $this->getLoggerDecorator()->debug('Sending REST request');

        return $request->send();
    }

    /**
     * @param Response $response
     * @param string $type
     * @param string $contentType
     * @return mixed
     */
    protected function processResponse(Response $response, $type, $contentType)
    {
        $result = $response->getBody(true);

        $converter = static::getConverter($contentType);

        if (is_null($type) || is_null($result)) {
            return $converter->toArray($result);
        }

        $this->getLoggerDecorator()->debug('Processing REST response');

        $gcEnabled = gc_enabled();
        if ($gcEnabled) {
            gc_disable();
        }

        if (substr($type, strlen($type) - 2, 2) == '[]') {
            $type = substr($type, 0, strlen($type) - 2);

            $result = array_map(
                function ($item) use ($converter, $type) {
                    return $this->deserializeData($converter, $item, $type);
                },
                $converter->toArray($result)
            );
        } else {
            $result = $this->deserializeData($converter, $result, $type);
        }

        if ($gcEnabled) {
            gc_enable();
        }

        $this->getLoggerDecorator()->debug('REST response processed');

        return $result;
    }

    /**
     * @param Model $request
     * @param string $uri
     * @param string $method
     * @param string[] $headers
     * @return \Guzzle\Http\Message\RequestInterface
     */
    private function request(Model $request, $uri, $method, $headers)
    {
        $this->getLoggerDecorator()->debug('Preparing REST request');

        $method = strtoupper($method);
        if ($method == 'GET' || $method == 'HEAD' || $method == 'TRACE') {
            $uri .= self::URI_PARAMS_SEPARATOR . $this->getUriConverter()->export($request);
            $request = null;
        } elseif (isset($request)) {
            $request = $this->getConverter($headers[self::CONTENT_TYPE])->export($request, $this->runValidation);
        }
        $request = $this->getTransport()->createRequest($method, $uri, $headers, $request);

        $this->getLoggerDecorator()->debug('REST request prepared');

        return $request;
    }

    /**
     * @param Converter $converter
     * @param string $data
     * @param string $type
     * @throws Exception\LogicalException
     * @return Model
     */
    private function deserializeData($converter, $data, $type)
    {
        return $converter->import($data, new $type());
    }
}