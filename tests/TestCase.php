<?php

namespace Freimaurerei\ServiceClient;

use Graze\Guzzle\JsonRpc\Message\Request;

require_once(__DIR__ . '/AbstractTestCase.php');

/**
 * Class TestCase
 * @package Freimaurerei\ServiceClient
 *
 * @property Client $client
 */
abstract class TestCase extends AbstractTestCase
{
    protected function getLastResponseResult()
    {
        return json_decode($this->history->getLastResponse()->getBody(true), true)['result'];
    }

    protected function assertRequest(Request $request, $method, array $params)
    {
        $this->assertSame($method, $request->getRpcField('method'));

        $sentParams = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($request->getRpcField('params')));
        foreach (new \RecursiveIteratorIterator(new \RecursiveArrayIterator($params)) as $param) {
            $sentParams->next();
            if (is_object($param)) {
                $this->assertEquals($param, $sentParams->current());
            } else {
                $this->assertSame($param, $sentParams->current());
            }
        }
    }

    /**
     * @param string $method
     * @param array $params
     * @param bool $runValidation
     */
    protected function assertLastRequest($method, array $params, $runValidation = true)
    {
        $requestParams = [];

        foreach ($params as $key => $param) {
            if ($param instanceof Model) {
                if ($runValidation) {
                    $param->validate();
                }
                $requestParams[$key] = $param->getAttributes();
            } else {
                $requestParams[$key] = $param;
            }
        }

        /** @var Request $request */
        $request = $this->history->getLastRequest();
        $this->assertRequest($request, $method, $requestParams);
    }
}