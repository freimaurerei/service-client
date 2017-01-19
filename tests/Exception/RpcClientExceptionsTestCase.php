<?php

namespace Freimaurerei\ServiceClient\Exception;

use Freimaurerei\ServiceClient\Client;
use Graze\Guzzle\JsonRpc\Message\ErrorResponse;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Message\Response;

class RpcClientExceptionsTestCase extends \PHPUnit_Framework_TestCase
{
    protected $mockUrl = 'http://some-mock-service.url';

    public function test_RcpClient_throws_LogicalException_on_ErrorResponse()
    {
        $method = 'testLogicalException';
        $errorCode = -1;
        $errorMessage = 'test error message';

        $client = $this->makeRpcClientMock([ 'sendRequest' ]);

        $errorResponse = $this->makeErrorResponse(uniqid(), $errorCode, $errorMessage);

        $client
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->equalTo($method))
            ->will($this->returnValue($errorResponse));

        $this->setExpectedException(
            LogicalException::class,
            $errorMessage,
            $errorCode
        );

        $client->call($method);
    }

    public function test_RcpClient_throws_TransportException_on_any_Guzzle_exception()
    {
        $method = 'testTransportException';

        $client = $this->makeRpcClientMock([ 'sendRequest' ]);

        $guzzleException = new RequestException('test guzzle exception', -2);

        $client
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->equalTo($method))
            ->will($this->throwException($guzzleException));

        $this->setExpectedException(
            TransportException::class,
            $guzzleException->getMessage(),
            $guzzleException->getCode()
        );

        $client->call($method);
    }

    /**
     * @param null $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|\Freimaurerei\ServiceClient\Client
     */
    protected function makeRpcClientMock($methods = null)
    {
        return $this->getMock(
            Client::class,
            $methods,
            [ $this->mockUrl ]
        );
    }

    /**
     * @param $id
     * @param $errorCode
     * @param $errorMessage
     * @return ErrorResponse
     */
    public function makeErrorResponse($id, $errorCode, $errorMessage)
    {
        return new ErrorResponse(
            new Response(200),
            [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => $errorCode,
                    'message' => $errorMessage
                ],
            ]
        );
    }
}