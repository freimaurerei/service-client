<?php

namespace Freimaurerei\ServiceClient;

class MethodCall
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $params;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $runValidation;

    public function __construct($method, array $params = [], $type = null, $runValidation = null)
    {
        $this->method = $method;
        $this->params = $params;
        $this->type = $type;
        $this->runValidation = $runValidation;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return null|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function getRunValidation()
    {
        return $this->runValidation;
    }
}