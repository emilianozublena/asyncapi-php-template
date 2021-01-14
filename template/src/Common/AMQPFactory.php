<?php
/**
 * Created by PhpStorm.
 * User: emiliano
 * Date: 30/12/20
 * Time: 11:39
 */

namespace {{ params.packageName }}\BrokerAPI\Common;

use {{ params.packageName }}\BrokerAPI\Handlers\HandlerContract;
use {{ params.packageName }}\BrokerAPI\Infrastructure\BrokerClientContract;
use {{ params.packageName }}\BrokerAPI\Infrastructure\AMQPBrokerClient;
use {{ params.packageName }}\BrokerAPI\Messages\MessageContract;
use {{ params.packageName }}\BrokerAPI\Applications\ApplicationContract;
use {{ params.packageName }}\BrokerAPI\Applications\Consumer;
use {{ params.packageName }}\BrokerAPI\Applications\Producer;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPFactory implements FactoryContract
{
    /** @var AMQPStreamConnection $brokerConnection */
    private $brokerConnection;

    /**
     * AMQPFactory constructor.
     * @param AMQPStreamConnection|null $brokerConnection
     */
    public function __construct(
        AMQPStreamConnection $brokerConnection = null
    ) {
        $this->brokerConnection = $brokerConnection;
    }

    /**
     * /**
     * @param array $config
     * @param null $brokerConnection
     * @return BrokerClientContract
     */
    public function createBrokerClient(
        array $config = []
    ): BrokerClientContract {

        if (empty($this->brokerConnection)) {
            $this->brokerConnection = new AMQPStreamConnection(
                $config[BROKER_HOST_KEY] ?? BROKER_HOST_DEFAULT,
                $config[BROKER_PORT_KEY] ?? BROKER_PORT_DEFAULT,
                $config[BROKER_USER_KEY] ?? BROKER_USER_DEFAULT,
                $config[BROKER_PASSWORD_KEY] ?? BROKER_PASSWORD_DEFAULT,
                $config[BROKER_VIRTUAL_HOST_KEY] ?? BROKER_VIRTUAL_HOST_DEFAULT
            );
        }

        return new AMQPBrokerClient($this->brokerConnection);
    }

    /**
     * @param string $applicationType
     * @param array $config
     * @return ApplicationContract
     */
    public function createApplication(
        string $applicationType,
        array $config = []
    ): ApplicationContract {
        $application = null;

        $brokerClient = $this->createBrokerClient($config);

        switch ($applicationType) {
            case PRODUCER_KEY:
                $application = new Producer(
                    $brokerClient
                );
                break;
            case CONSUMER_KEY:
                $application = new Consumer(
                    $brokerClient
                );
                break;
        }
        $application->setFactory($this);

        return $application;
    }

    /**
     * @param string $handlerType
     * @param array $config
     * @return HandlerContract
     */
    public function createHandler(
        string $handlerType,
        array $config = []
    ): HandlerContract
    {
        return new $handlerType($config);
    }

    /**
     * @param MessageContract $message
     * @param array $settings
     * @return MessageContract
     */
    public function createMessage(
        MessageContract $message,
        array $settings = []
    ): MessageContract {
        $message->setSettings($settings)
            ->setPayload(
                new AMQPMessage(json_encode($message))
            );

        return $message;
    }
}
