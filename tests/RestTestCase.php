<?php

namespace Freimaurerei\ServiceClient;

use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Http\Message\Request;
use Freimaurerei\ServiceModel\Converter\Converter;

require_once(__DIR__ . '/AbstractTestCase.php');

/**
 * Class RestTestCase
 * @package Freimaurerei\ServiceClient
 *
 * @property RestClient $client
 */
abstract class RestTestCase extends AbstractTestCase
{
    protected function getLastResponseResult()
    {
        switch ($this->history->getLastRequest()->getHeader(RestClient::CONTENT_TYPE)) {
            case RestClient::CONTENT_TYPE_JSON:
                $converterType = RestClient::TYPE_JSON;
                break;
            case RestClient::CONTENT_TYPE_XML:
                $converterType = RestClient::TYPE_XML;
                break;
            default:
                $this->fail('Incorrect content type');
        }

        /** @noinspection PhpUndefinedVariableInspection */
        return Converter::factory($converterType)->toArray($this->history->getLastResponse()->getBody(true));
    }

    /**
     * @param Model $request
     * @param string $uri
     * @param string $method
     * @param array $headers
     * @param string $contentType
     * @param bool $runValidation
     */
    protected function assertLastRequest(
        Model $request,
        $uri = '',
        $method = 'GET',
        $headers = [],
        $contentType = RestClient::CONTENT_TYPE_JSON,
        $runValidation = true
    ) {
        if ($runValidation) {
            $request->validate();
        }

        if (!isset($uri)) {
            $uri = '';
        }

        /** @var Request $lastRequest */
        $lastRequest = $this->history->getLastRequest();
        $this->assertRequest(
            $lastRequest,
            $uri,
            $method,
            array_merge($headers, [RestClient::CONTENT_TYPE => $contentType, RestClient::ACCEPT => $contentType]),
            $request
        );
    }

    private function assertRequest(
        Request $request,
        $uri = '',
        $method = 'GET',
        array $headers = [],
        Model $expectedRequest
    ) {
        foreach ($headers as $key => $value) {
            $this->assertSame($value, (string)$request->getHeader($key));
        }

        $url = $request->getUrl(true);

        $this->assertSame($uri, ltrim($url->getPath(), '/'));

        $this->assertSame($method, $request->getMethod());

        if ($method == 'GET' || $method == 'HEAD' || $method == 'TRACE') {
            $this->assertSame(
                http_build_query($expectedRequest->getAttributes(), null, '&', PHP_QUERY_RFC3986),
                (string)$url->getQuery()
            );
        } elseif ($expectedRequest->getAttributes()) {
            $this->assertInstanceOf(EntityEnclosingRequest::class, $request);
            /** @var \Guzzle\Http\Message\EntityEnclosingRequest $request */
            switch ($headers[RestClient::CONTENT_TYPE]) {
                case RestClient::CONTENT_TYPE_JSON:
                    $type = RestClient::TYPE_JSON;
                    break;
                case RestClient::CONTENT_TYPE_XML:
                    $type = RestClient::TYPE_XML;
                    break;
            }

            $expected = Converter::factory($type)->export($expectedRequest);
            $actual = (string)$request->getBody();

            if (isset($type)) {
                switch ($type) {
                    case RestClient::TYPE_XML:
                        $this->assertXmlStringEqualsXmlString($expected, $actual);
                        break;
                    case RestClient::TYPE_JSON:
                        $this->assertJsonStringEqualsJsonString($expected, $actual);
                        break;
                }
            }
        }
    }
}