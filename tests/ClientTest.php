<?php

namespace Freimaurerei\ServiceClient;

use Graze\Guzzle\JsonRpc\Message\BatchRequest;

class ClientTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $client = $this->createMockClient(Client::class);
        $this->init($client, __DIR__ . '/Mock/Responses/RPC');
    }

    public function testSingleResponse()
    {
        $this->expectsResponse('responseSingle');
        $result = $this->client->call('test', [1], MockModel::class);

        $this->assertInstanceOf(MockModel::class, $result);
    }

    public function testMultipleResponse()
    {
        $this->expectsResponse('responseMultiple');
        $resultSet = $this->client->call('test', [1], MockModel::class . '[]');

        $this->assertInternalType('array', $resultSet);
        foreach ($resultSet as $result) {
            $this->assertInstanceOf(MockModel::class, $result);
        }
    }

    public function testBatch()
    {
        $this->expectsResponse('responseBatch');
        /** @var MockModel[] $results */
        $results = $this->client->batch(
            [
                new MethodCall('test', [1], MockModel::class),
                new MethodCall('test', [2], MockModel::class)
            ]
        );

        $ids = array_keys($results);
        $this->assertBatch($results, $ids);
    }

    public function testStartBatch()
    {
        $this->expectsResponse('responseBatch');

        $this->client->startBatch();

        $ids[] = $this->client->call('test', [1], MockModel::class);
        $ids[] = $this->client->call('test', [2], MockModel::class);

        $results = $this->client->stopBatch();

        $this->assertBatch($results, $ids);
    }

    private function assertBatch(array $results, array $ids)
    {
        $this->assertInstanceOf(BatchRequest::class, $this->history->getLastRequest());

        foreach ($results as $result) {
            $this->assertInstanceOf(MockModel::class, $result);
        }

        $this->assertSame($ids, array_keys($results));
    }
}