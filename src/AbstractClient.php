<?php

namespace Freimaurerei\ServiceClient;

use Freimaurerei\ServiceClient\Logger\AbstractPsrLoggerDecorator;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Log\MessageFormatter;
use Guzzle\Log\PsrLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractClient
{
    const OPT_READ_TIMEOUT = 'timeout';
    const OPT_CONNECT_TIMEOUT = 'connect_timeout';

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GuzzleClient
     */
    private $transport;

    /**
     * @var EventSubscriberInterface
     */
    private $timerPlugin;

    /**
     * @var AbstractPsrLoggerDecorator
     */
    private $loggerDecorator;

    /**
     * @var int Стандартная вложенность класса конечного клиента
     */
    protected $conventionalNamespacePartsCount = 3;

    /**
     * @var int Стандартный сдвиг в полном имени класса-клиента для получения значимой части имени клиента
     */
    protected $conventionalServiceNameOffset = 1;

    /**
     * @var int Стандартная длина значимой части в полном имени класса-клиента
     */
    protected $conventionalServiceNameLength = 1;

    /**
     * @var bool Запускать ли валидацию запроса при вызове метода сервиса
     */
    public $runValidation = true;

    /**
     * @var bool
     */
    private $_previousRunValidation;

    /**
     * @param string $baseUrl
     * @param \Psr\Log\LoggerInterface $logger
     * @return \Freimaurerei\ServiceClient\AbstractClient
     */
    public function __construct($baseUrl, LoggerInterface $logger = null)
    {
        $this->baseUrl = $baseUrl;
        $this->logger = $logger;
    }

    /**
     * @return GuzzleClient
     */
    abstract protected function getFinalClientTransport();

    /**
     * @return EventSubscriberInterface
     */
    abstract protected function getFinalClientTimerPlugin();

    /**
     * @param LoggerInterface $logger
     * @return AbstractPsrLoggerDecorator
     */
    abstract protected function getFinalClientLoggerDecorator(LoggerInterface $logger);

    /**
     * @return GuzzleClient
     */
    protected function getTransport()
    {
        if (is_null($this->transport)) {
            $this->transport = $this->getFinalClientTransport();

            $this->attachSubscribers();
        }

        return $this->transport;
    }

    /**
     * @return AbstractPsrLoggerDecorator
     */
    protected function getLoggerDecorator()
    {
        if (!isset($this->loggerDecorator)) {
            $this->loggerDecorator = $this->getFinalClientLoggerDecorator($this->getLogger());
        }

        return $this->loggerDecorator;
    }

    protected function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->getTransport()->addSubscriber($subscriber);
    }

    protected function attachSubscribers()
    {
        $this->addLogger($this->getLogger());
    }

    protected function getServiceName()
    {
        $serviceName = get_class($this);
        $classParts = explode('\\', $serviceName);

        if (sizeof($classParts) === $this->conventionalNamespacePartsCount) {
            $serviceName = implode(
                '\\',
                array_slice($classParts, $this->conventionalServiceNameOffset, $this->conventionalServiceNameLength)
            );
        }

        return $serviceName;
    }

    /**
     * @param bool|null $runValidation
     */
    protected function setRunValidation($runValidation)
    {
        if (isset ($runValidation)) {
            $this->_previousRunValidation = $this->runValidation;
            $this->runValidation = $runValidation;
        }
    }

    protected function restoreRunValidation()
    {
        if (isset($this->_previousRunValidation)) {
            $this->runValidation = $this->_previousRunValidation;
        }
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger()
    {
        if (is_null($this->logger)) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * @param int $readTimeout seconds
     */
    public function setReadTimeout($readTimeout)
    {
        $this->getTransport()->setDefaultOption(self::OPT_READ_TIMEOUT, $readTimeout);
    }

    /**
     * @return int seconds
     */
    public function getReadTimeout()
    {
        return $this->getTransport()->getDefaultOption(self::OPT_READ_TIMEOUT);
    }

    /**
     * @param int $connectTimeout seconds
     */
    public function setConnectTimeout($connectTimeout)
    {
        $this->getTransport()->setDefaultOption(self::OPT_CONNECT_TIMEOUT, $connectTimeout);
    }

    /**
     * @return int seconds
     */
    public function getConnectTimeout()
    {
        return $this->getTransport()->getDefaultOption(self::OPT_CONNECT_TIMEOUT);
    }

    public function addLogger(LoggerInterface $logger = null)
    {
        $this->addSubscriber(
            new LogPlugin(
                new PsrLogAdapter(
                    isset($logger)
                        ? $this->getFinalClientLoggerDecorator($logger)
                        : $this->getLoggerDecorator()
                ),
                MessageFormatter::DEBUG_FORMAT
            )
        );
    }
}