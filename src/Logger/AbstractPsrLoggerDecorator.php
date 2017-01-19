<?php

namespace Freimaurerei\ServiceClient\Logger;

use Freimaurerei\ServiceClient\Interfaces\ICanGetServiceNameKey;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

abstract class AbstractPsrLoggerDecorator extends AbstractLogger implements ICanGetServiceNameKey
{
    /**
     * @var string
     */
    protected $serviceName;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct($serviceName, LoggerInterface $logger)
    {
        $this->serviceName = $serviceName;
        $this->logger = $logger;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        $context[static::getServiceNameKey()] = $this->serviceName;

        $this->logger->log($level, $message, $context);
    }
}