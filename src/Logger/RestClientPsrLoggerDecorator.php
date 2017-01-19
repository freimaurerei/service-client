<?php

namespace Freimaurerei\ServiceClient\Logger;

class RestClientPsrLoggerDecorator extends AbstractPsrLoggerDecorator
{
    /**
     * @return string
     */
    public static function getServiceNameKey()
    {
        return 'restClient.serviceName';
    }
}