<?php

namespace Freimaurerei\ServiceClient\Plugin;

use Freimaurerei\ServiceClient\Logger\RestClientPsrLoggerDecorator;

class GuzzleRestServiceClientYiiLoggerPsrAdapter extends AbstractServiceClientYiiLogger
{
    /**
     * @param $string
     * @return string
     */
    private function stringToHtml($string)
    {
        return strtr($string, ["\r\n" => '<br>', "\n\r" => '<br>', "\n" => '<br>', "\r" => '<br>']);
    }

    public static function getServiceNameKey()
    {
        return RestClientPsrLoggerDecorator::getServiceNameKey();
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
            /** @var \Guzzle\Http\Message\Request $request */
            $request = $context['request'];
            $url = $request->getUrl();

            \Yii::getLogger()->log(
                "Request sent to $url <br> " .
                $this->stringToHtml((string)$request),
                $level,
                "services.$serviceName." . self::CATEGORY_REQUEST
            );
        }

        if (isset($context['response'])) {
            /** @var \Guzzle\Http\Message\Response $response */
            $response = $context['response'];

            \Yii::getLogger()->log(
                'Response received<br> ' .
                $this->stringToHtml((string)$response),
                $level,
                "services.$serviceName." . self::CATEGORY_RESPONSE
            );
        }
    }
}