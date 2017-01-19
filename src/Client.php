<?php

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

namespace Freimaurerei\ServiceClient;

use Freimaurerei\ServiceClient\Exception\LogicalException;
use Freimaurerei\ServiceClient\Exception\TransportException;
use Freimaurerei\ServiceClient\Logger\ClientPsrLoggerDecorator;
use Freimaurerei\ServiceClient\Plugin\GuzzleJsonRpcServiceClientTimerPlugin;
use Graze\Guzzle\JsonRpc\JsonRpcClient;
use Graze\Guzzle\JsonRpc\Message\ErrorResponse;
use Graze\Guzzle\JsonRpc\Message\Response;
use Guzzle\Common\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Freimaurerei\ServiceModel\Exception\ValidationException;

/**
 * Class Client
 * @package Freimaurerei\ServiceClient
 *
 * @method \Graze\Guzzle\JsonRpc\JsonRpcClient getTransport()
 */
class Client extends AbstractClient
{
    /**
     * @var bool
     */
    private $isBatching = false;

    /**
     * @var \Graze\Guzzle\JsonRpc\Message\Request[]
     */
    private $requests = [];

    /**
     * @var string[]
     */
    private $types = [];

    /**
     * @return JsonRpcClient
     */
    protected function getFinalClientTransport()
    {
        return new JsonRpcClient($this->getBaseUrl());
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
     * @return Logger\AbstractPsrLoggerDecorator
     */
    protected function getFinalClientLoggerDecorator(LoggerInterface $logger)
    {
        return new ClientPsrLoggerDecorator(
            static::getServiceName(),
            $logger
        );
    }

    /**
     * В зависимости от того включен ли batch mode возвращает результат запроса
     * или ID очередного запроса, добавляемого в batch
     *
     * @param string $method
     * @param array $params
     * @param string $type
     * @param bool $runValidation
     *
     * @throws Exception\TransportException
     * @throws Exception\LogicalException
     * @return mixed
     */
    public function call($method, array $params = [], $type = null, $runValidation = null)
    {
        $this->setRunValidation($runValidation);

        if ($this->isBatching) {
            $request = $this->request($method, $params);
            $this->restoreRunValidation();

            $this->requests[] = $request;
            $id = $request->getRpcField('id');
            $this->types[$id] = $type;

            return $id;
        }

        try {
            $response = $this->sendRequest($method, $params);

            $this->restoreRunValidation();

            if ($response instanceof ErrorResponse) {
                $data = $response->getData();
                throw LogicalException::createExceptionWithErrorData(
                    $response->getMessage(),
                    $response->getCode(),
                    isset($data['problem']) ? $data['problem'] : []
                );
            }

            return $this->processResponse($response, $type);
        } catch (GuzzleException $e) {
            $this->restoreRunValidation();
            throw new TransportException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Звпускает batch mode. После запуска все call() будут добавлять запросы в batch вместо прямого вызова.
     *
     * @return bool
     * @throws Exception\LogicalException
     */
    public function startBatch()
    {
        if ($this->isBatching) {
            throw new LogicalException('Trying to start batch mode while batching.');
        }

        return $this->isBatching = true;
    }

    /**
     * Останавливает batch mode и отправляет batch-запрос.
     * Возвращает массив ответов, проиндексированный ID'шниками запросов.
     *
     * @return array
     * @throws Exception\LogicalException
     */
    public function stopBatch()
    {
        if (!$this->isBatching) {
            throw new LogicalException('Trying to send batch request without batch mode enabled.');
        }

        $this->isBatching = false;

        return $this->batch();
    }

    /**
     * Возвращает массив ответов проиндексированный ID'шниками запросов.
     *
     * @param MethodCall[] $requests
     * @throws Exception\TransportException
     * @return array
     */
    public function batch(array $requests = null)
    {
        if (isset($requests)) {
            $types = [];
            array_walk(
                $requests,
                function (&$request) use (&$types) {
                    /** @var MethodCall $request */
                    $type = $request->getType();
                    $this->setRunValidation($request->getRunValidation());
                    $request = $this->request($request->getMethod(), $request->getParams());
                    $this->restoreRunValidation();
                    /** @var \Graze\Guzzle\JsonRpc\Message\Request $request */
                    $types[$request->getRpcField('id')] = $type;
                }
            );
        } else {
            $requests = $this->requests;
            $this->requests = [];

            $types = $this->types;
            $this->types = [];
        }

        try {
            $this->getLoggerDecorator()->debug('Sending batch request');
            /** @var Response[] $responses */
            $responses = $this->getTransport()->batch($requests)->send();
        } catch (GuzzleException $e) {
            throw new TransportException($e->getMessage(), $e->getCode(), $e);
        }

        // Переиндексируем ответы в соответствии с их ID, т.к. батчинг не гаратирует ответа в том же порядке
        /** @var Response[] $indexedResponses */
        $indexedResponses = [];
        foreach ($responses as $response) {
            $indexedResponses[$response->getId()] = $response;
        }

        $result = [];

        foreach ($requests as $request) {
            $id = $request->getRpcField('id');
            if (!isset($indexedResponses[$id])) {
                $result[$id] = null;
                $this->getLoggerDecorator()->warning("Did not receive response for request with ID \"$id\".");
            } else {
                $response = $indexedResponses[$id];
                if ($response instanceof ErrorResponse) {
                    $this->getLoggerDecorator()->warning(
                        "Error response for request with ID \"$id\". {$response->getMessage()}"
                    );
                    $result[$id] = null;
                } else {
                    $result[$id] = $this->processResponse($response, $types[$id]);
                }
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function getRequestUniqueId()
    {
        return uniqid();
    }

    /**
     * @param $method
     * @param $params
     * @return Response
     */
    protected function sendRequest($method, $params)
    {
        $request = $this->request($method, $params);

        $this->getLoggerDecorator()->debug('Sending request');

        return $request->send();
    }

    /**
     * @param Response $response
     * @param string $type
     * @return array|mixed|Model
     */
    protected function processResponse(Response $response, $type)
    {
        $result = $response->getResult();

        if (is_null($type) || is_null($result)) {
            return $result;
        }

        $this->getLoggerDecorator()->debug('Processing response');

        $gcEnabled = gc_enabled();
        if ($gcEnabled) {
            gc_disable();
        }

        if (substr($type, strlen($type) - 2, 2) == '[]') {
            $type = substr($type, 0, strlen($type) - 2);

            $result = array_map(
                function ($item) use ($type) {
                    return is_null($item) ? $item : $this->deserializeData($item, $type);
                },
                $result
            );
        } else {
            $result = $this->deserializeData($result, $type);
        }

        if ($gcEnabled) {
            gc_enable();
        }

        $this->getLoggerDecorator()->debug('Response processed');

        return $result;
    }

    /**
     * @param array $params
     *
     * @throws ValidationException
     * @return array
     */
    private function toArray(array $params)
    {
        array_walk(
            $params,
            function ($param) {
                if ($param instanceof Model) {
                    if ($this->runValidation && !$param->validate()) {
                        $errorMessage = '';
                        foreach ($param->getErrors() as $field => $errors) {
                            $errorMessage .= "$field - ";
                            $errorMessage .= implode(',', $errors);
                            $errorMessage .= '; ';
                        }
                        throw new ValidationException(\Yii::t(
                            'converter',
                            'Error validating "{model}" model data. Errors are: {errors}',
                            [
                                '{model}' => get_class($param),
                                '{errors}' => $errorMessage
                            ]
                        ));
                    }
                    $param = (object)$param->getAttributes();
                } elseif (!$param instanceof \StdClass && is_object($param)) {
                    $this->getLoggerDecorator()->warning(
                        'Trying to serialize object not implementing ' . Model::class . '!'
                    );
                }
            }
        );

        return $params;
    }

    /**
     * @param $method
     * @param $params
     * @return \Graze\Guzzle\JsonRpc\Message\Request
     */
    private function request($method, $params)
    {
        $this->getLoggerDecorator()->debug('Preparing request.');

        $request = $this->getTransport()->request($method, $this->getRequestUniqueId(), $this->toArray($params));

        $this->getLoggerDecorator()->debug('Request prepared');
        return $request;
    }

    /**
     * @param array $data
     * @param string $type
     * @throws Exception\LogicalException
     * @return Model
     */
    private function deserializeData(array $data, $type)
    {
        /** @var Model $model */
        $model = new $type();
        $model->setAttributes($data, false);
        return $model;
    }
}