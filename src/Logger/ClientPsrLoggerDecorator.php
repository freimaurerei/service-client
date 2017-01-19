<?php

namespace Freimaurerei\ServiceClient\Logger;

class ClientPsrLoggerDecorator extends AbstractPsrLoggerDecorator
{
    /**
     * @return string
     */
    public static function getServiceNameKey()
    {
        return 'rpcClient.serviceName';
    }
}