<?php

namespace Freimaurerei\ServiceClient\Plugin;

use Freimaurerei\ServiceClient\Logger\ClientPsrLoggerDecorator;

class GuzzleJsonRpcServiceClientYiiLoggerPsrAdapter extends AbstractServiceClientYiiLogger
{
    public static function getServiceNameKey()
    {
        return ClientPsrLoggerDecorator::getServiceNameKey();
    }

    public function log($level, $message, array $context = [])
    {
        $serviceName = $this->getServiceName($context);
        $level = $this->getLevel($level);

        if (empty($context['request']) && empty($context['response']) && !empty($message)) {
            \Yii::getLogger()->log(
                $message,
                $level,
                "services.$serviceName." . self::CATEGORY_MESSAGE
            );
        }

        if (isset($context['request'])) {
            /** @var \Graze\Guzzle\JsonRpc\Message\Request $request */
            $request = $context['request'];
            $url = $request->getUrl();

            \Yii::getLogger()->log(
                "Request sent to $url <br>" .
                (string)$request->getBody(),
                $level,
                "services.$serviceName." . self::CATEGORY_REQUEST
            );
        }

        if (isset($context['response'])) {
            /** @var \Graze\Guzzle\JsonRpc\Message\Response $response */
            $response = $context['response'];

            \Yii::getLogger()->log(
                'Response received<br>' .
                (string)$response->getBody(),
                $level,
                "services.$serviceName." . self::CATEGORY_RESPONSE
            );
        }
    }
}