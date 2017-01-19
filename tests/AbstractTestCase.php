<?php

namespace Freimaurerei\ServiceClient;

use Guzzle\Http\Message\Response;
use Guzzle\Plugin\History\HistoryPlugin;
use Guzzle\Plugin\Mock\MockPlugin;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    private $_mockUniqueId = 0;

    /**
     * @var AbstractClient
     */
    protected $client;

    /**
     * @var MockPlugin
     */
    protected $server;

    /**
     * @var HistoryPlugin
     */
    protected $history;

    protected $responseDirectory;

    /**
     * @return array
     */
    abstract protected function getLastResponseResult();

    /**
     * @param AbstractClient $client
     * @param string $responseDirectory
     */
    protected function init(AbstractClient $client, $responseDirectory)
    {
        $this->client = $client;

        $mockPlugin = new MockPlugin();

        $historyPlugin = new HistoryPlugin();
        $historyPlugin->setLimit(1);

        $this->client->addSubscriber($mockPlugin);
        $this->client->addSubscriber($historyPlugin);

        $this->history = $historyPlugin;
        $this->server = $mockPlugin;
        $this->responseDirectory = $responseDirectory;
    }

    /**
     * @param string $className
     * @param \Psr\Log\LoggerInterface|null $logger
     * @return \PHPUnit_Framework_MockObject_MockObject|\Freimaurerei\ServiceClient\AbstractClient
     */
    protected function createMockClient($className, $logger = null)
    {
        $serviceUrl = 'http://some-service.i/';

        if (!isset($logger)) {
            $logger = new Logger('Test');
            $logger->pushHandler(new StreamHandler('php://output'));
        }

        // Подменяем возвращаемое значение для getRequestUniqueId на 1
        $client = $this->getMock(
            $className,
            ['getRequestUniqueId'],
            [$serviceUrl, $logger]
        );

        $client->expects($this->any())
            ->method('getRequestUniqueId')
            ->will($this->returnCallback([$this, 'getNextUniqueId']));

        return $client;
    }

    public function getNextUniqueId()
    {
        return ++$this->_mockUniqueId;
    }

    /**
     * @param string $name
     * @param string $extension
     */
    protected function expectsResponse($name, $extension = 'json')
    {
        $content = file_get_contents($this->responseDirectory . "/$name.$extension");
        $this->server->addResponse(new Response(200, null, $content));
    }

    /**
     * @param Model[]|ArrayCollection[] $models
     * @return array
     */
    protected function getMultipleModelsAttributes(array $models)
    {
        return array_map(
            function ($model) {
                /**
                 * @param Model|ArrayCollection $model
                 */
                return $model->getAttributes();
            },
            $models
        );
    }

    /**
     * @param Model $expected
     * @param Model $actual
     */
    protected function assertModelsEqual(Model $expected, Model $actual)
    {
        foreach ($expected->attributeNames() as $attributeName) {
            $expectedValue = $expected->$attributeName;
            $actualValue = $actual->$attributeName;

            if ($expectedValue instanceof Model) {
                $this->assertModelsEqual($expectedValue, $actualValue);
            } elseif ($expectedValue instanceof ArrayCollection) {
                $this->assertArrayCollectionsEqual($expectedValue, $actualValue);
            } elseif (is_array($expectedValue)) {
                foreach ($expectedValue as $key => $expectedValueElement) {
                    if ($expectedValueElement instanceof Model) {
                        $this->assertModelsEqual($expectedValueElement, $actualValue[$key]);
                    } elseif ($expectedValueElement instanceof ArrayCollection) {
                        $this->assertArrayCollectionsEqual($expectedValueElement, $actualValue[$key]);
                    } else {
                        $this->assertSame($expectedValueElement, $actualValue[$key]);
                    }
                }
            } else {
                $this->assertSame($expectedValue, $actualValue);
            }
        }
    }

    /**
     * @param ArrayCollection $expected
     * @param ArrayCollection $actual
     */
    private function assertArrayCollectionsEqual(ArrayCollection $expected, ArrayCollection $actual)
    {
        foreach ($expected as $key => $collection) {
            foreach ($collection as $itemKey => $item) {
                if ($item instanceof Model) {
                    $this->assertModelsEqual($item, $actual[$key][$itemKey]);
                } elseif ($item instanceof ArrayCollection) {
                    $this->assertArrayCollectionsEqual($item, $actual[$key][$itemKey]);
                }
            }
        }
    }

    /**
     * @param \ArrayAccess|array $array
     */
    private function ksortRecursive(&$array)
    {
        if ($array instanceof \ArrayAccess) {
            $array = (array)$array;
        }
        ksort($array);
        foreach ($array as &$item) {
            if (isset($item) && !is_scalar($item)) {
                $this->ksortRecursive($item);
            }
        }
    }

    /**
     * @param array $expected
     * @param Model $actual
     */
    protected function assertSameData(array $expected, Model $actual)
    {
        $this->ksortRecursive($expected);
        $expectedData = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($expected));
        $expectedData->next();
        $actualData = $actual->getAttributes();
        $this->ksortRecursive($actualData);
        foreach (new \RecursiveIteratorIterator(new \RecursiveArrayIterator($actualData)) as $key => $modelAttribute) {
            if ($expectedData->key() === $key) {
                if (is_object($modelAttribute)) {
                    $this->assertEquals($modelAttribute, $expectedData->current());
                } else {
                    $this->assertSame($modelAttribute, $expectedData->current());
                }
                $expectedData->next();
            }
        }
    }

    /**
     * Проверяет корректно ли проставились поля в десериализованный объект указанного класса
     *
     * @param mixed $response
     * @param string $className
     */
    protected function assertLastResponse($response, $className = null)
    {
        $result = $this->getLastResponseResult();
        if (isset($className)) {
            if (substr($className, strlen($className) - 2, 2) == '[]') {
                /** @var Model[] $response */
                $className = substr($className, 0, strlen($className) - 2);
                foreach ($result as $key => $element) {
                    $this->assertSameData($element, $response[$key]);

                    /** @var Model $expectedResponseElement */
                    $expectedResponseElement = new $className();
                    $expectedResponseElement->setAttributes($element, false);
                    $this->assertModelsEqual($expectedResponseElement, $response[$key]);
                }
            } else {
                $this->assertSameData($result, $response);

                /** @var Model $expectedResponse */
                $expectedResponse = new $className();
                $expectedResponse->setAttributes($result, false);
                $this->assertModelsEqual($expectedResponse, $response);
            }
        } else {
            $this->assertSame($result, $response);
        }
    }
}