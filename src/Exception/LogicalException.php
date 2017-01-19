<?php

namespace Freimaurerei\ServiceClient\Exception;

class LogicalException extends \Exception implements RpcServiceClientException, RestServiceClientException
{
    /**
     * @var string[]
     */
    private $errorData;

    /**
     * @return string[] Получает данные об ошибке
     */
    public function getErrorData()
    {
        return $this->errorData;
    }

    public static function createExceptionWithErrorData($message = "", $code = 0, $errorData = [])
    {
        $exception = new static($message, $code);
        $exception->errorData = $errorData;
        return $exception;
    }
}