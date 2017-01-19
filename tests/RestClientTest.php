<?php

namespace Freimaurerei\ServiceClient;

class RestClientTest extends RestTestCase
{
    private $uri = 'path';

    protected function setUp()
    {
        parent::setUp();

        $client = $this->createMockClient(RestClient::class);
        $this->init($client, __DIR__ . '/Mock/Responses/REST');
    }

    public function testSingleResponse()
    {
        $model = new MockModel();
        $model->id = '1';
        $model->name = 'some name';
        $method = 'POST';

        $this->expectsResponse('responseSingle');

        $this->assertLastResponse(
            $this->client->call($model, $this->uri, $method, MockModel::class),
            MockModel::class
        );
        $this->assertLastRequest($model, $this->uri, $method);
    }

    public function testMultipleResponse()
    {
        $model = new MockModel();
        $model->id = '1';
        $model->name = 'some name';
        $method = 'POST';

        $this->expectsResponse('responseMultiple');

        $this->assertLastResponse(
            $this->client->call($model, $this->uri, $method, MockModel::class . '[]'),
            MockModel::class . '[]'
        );
        $this->assertLastRequest($model, $this->uri, $method);
    }

    public function testXmlSingleResponse()
    {
        $model = new MockModel();
        $model->id = '1';
        $model->name = 'some name';
        $method = 'POST';

        $this->expectsResponse('responseSingle', 'xml');

        $this->assertLastResponse(
            $this->client->call($model, $this->uri, $method, MockModel::class, [], RestClient::CONTENT_TYPE_XML),
            MockModel::class
        );
        $this->assertLastRequest($model, $this->uri, $method, [], RestClient::CONTENT_TYPE_XML);
    }

    public function testXmlMultipleResponse()
    {
        $model = new MockModel();
        $model->id = '1';
        $model->name = 'some name';
        $method = 'POST';

        $this->expectsResponse('responseMultiple', 'xml');

        $this->assertLastResponse(
            $this->client->call(
                $model,
                $this->uri,
                $method,
                MockModel::class . '[]',
                [],
                RestClient::CONTENT_TYPE_XML
            ),
            MockModel::class . '[]'
        );
        $this->assertLastRequest($model, $this->uri, $method, [], RestClient::CONTENT_TYPE_XML);
    }

    public function testSingleResponseGet()
    {
        $model = new MockModel();
        $model->id = '1';
        $model->name = 'some name';
        $method = 'GET';

        $this->expectsResponse('responseSingle');

        $this->assertLastResponse(
            $this->client->call($model, $this->uri, $method, MockModel::class),
            MockModel::class
        );
        $this->assertLastRequest($model, $this->uri, $method);
    }

    public function testMultipleResponseGet()
    {
        $model = new MockModel();
        $model->id = '1';
        $model->name = 'some name';
        $method = 'GET';

        $this->expectsResponse('responseMultiple');

        $this->assertLastResponse(
            $this->client->call($model, $this->uri, $method, MockModel::class . '[]'),
            MockModel::class . '[]'
        );
        $this->assertLastRequest($model, $this->uri, $method);
    }

    public function testXmlSingleResponseGet()
    {
        $model = new MockModel();
        $model->id = '1';
        $model->name = 'some name';
        $method = 'GET';

        $this->expectsResponse('responseSingle', 'xml');

        $this->assertLastResponse(
            $this->client->call($model, $this->uri, $method, MockModel::class, [], RestClient::CONTENT_TYPE_XML),
            MockModel::class
        );
        $this->assertLastRequest($model, $this->uri, $method, [], RestClient::CONTENT_TYPE_XML);
    }

    public function testXmlMultipleResponseGet()
    {
        $model = new MockModel();
        $model->id = '1';
        $model->name = 'some name';
        $method = 'GET';

        $this->expectsResponse('responseMultiple', 'xml');

        $this->assertLastResponse(
            $this->client->call(
                $model,
                $this->uri,
                $method,
                MockModel::class . '[]',
                [],
                RestClient::CONTENT_TYPE_XML
            ),
            MockModel::class . '[]'
        );
        $this->assertLastRequest($model, $this->uri, $method, [], RestClient::CONTENT_TYPE_XML);
    }
}