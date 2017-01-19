<?php

namespace Freimaurerei\ServiceClient\Plugin;

use Freimaurerei\ServiceClient\Interfaces\ICanGetServiceNameKey;
use Psr\Log\AbstractLogger as BaseLogger;
use Psr\Log\LogLevel;
use yii\log\Logger;

abstract class AbstractServiceClientYiiLogger extends BaseLogger implements ICanGetServiceNameKey
{
    const CATEGORY_REQUEST = 'request';
    const CATEGORY_RESPONSE = 'response';
    const CATEGORY_MESSAGE = 'message';

    private static $logLevelsMap = [
        LogLevel::DEBUG => Logger::LEVEL_TRACE,
        LogLevel::ALERT => Logger::LEVEL_ERROR,
        LogLevel::CRITICAL => Logger::LEVEL_ERROR,
        LogLevel::EMERGENCY => Logger::LEVEL_ERROR,
        LogLevel::ERROR => Logger::LEVEL_ERROR,
        LogLevel::INFO => Logger::LEVEL_INFO,
        LogLevel::NOTICE => Logger::LEVEL_WARNING,
        LogLevel::WARNING => Logger::LEVEL_WARNING,
    ];

    /**
     * @param array $context
     * @return string
     */
    protected function getServiceName(array $context)
    {
        return empty($context[static::getServiceNameKey()]) ? 'unknown' : $context[static::getServiceNameKey()];
    }

    protected function getLevel($level)
    {
        return self::$logLevelsMap[$level];
    }
}