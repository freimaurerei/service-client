<?php

namespace Freimaurerei\ServiceClient\Exception;

use Freimaurerei\ServiceClient\MockModel;
use Freimaurerei\ServiceClient\RestClient;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Message\Response;

class RestClientExceptionsTestCase extends \PHPUnit_Framework_TestCase
{
    protected $mockUrl = 'http://some-mock-service.url';

    public function test_RcpClient_throws_LogicalException_on_ErrorResponse()
    {
        $request = new MockModel();
        $errorCode = 500;
        $errorMessage = 'test error message';

        $client = $this->makeRestClientMock([ 'sendRequest' ]);

        $errorResponse = $this->makeErrorResponse($errorCode, $errorMessage);

        $client
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->equalTo($request))
            ->will($this->returnValue($errorResponse));

        $this->setExpectedException(
            LogicalException::class,
            $errorMessage,
            $errorCode
        );

        $client->call($request);
    }

    public function test_RcpClient_throws_TransportException_on_any_Guzzle_exception()
    {
        $request = new MockModel();

        $client = $this->makeRestClientMock(['sendRequest' ]);

        $guzzleException = new RequestException('test guzzle exception', -2);

        $client
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->equalTo($request))
            ->will($this->throwException($guzzleException));

        $this->setExpectedException(
            TransportException::class,
            $guzzleException->getMessage(),
            $guzzleException->getCode()
        );

        $client->call($request);
    }

    /**
     * @param null $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|\Freimaurerei\ServiceClient\RestClient
     */
    protected function makeRestClientMock($methods = null)
    {
        return $this->getMock(
            RestClient::class,
            $methods,
            [ $this->mockUrl ]
        );
    }

    /**
     * @param $errorCode
     * @param $errorMessage
     * @return Response
     */
    public function makeErrorResponse($errorCode, $errorMessage)
    {
        return new Response($errorCode, null, $errorMessage);
    }
}