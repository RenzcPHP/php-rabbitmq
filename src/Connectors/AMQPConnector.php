<?php

namespace Burning\PhpRabbitmq\Connectors;

use Illuminate\Queue\Connectors\ConnectorInterface;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Burning\PhpRabbitmq\Drivers\AMQP;

class AMQPConnector implements ConnectorInterface
{
    private $connection;

    /**
     * Establish a queue connection.
     *
     * @param array $config
     *
     * @return Valsun\MessageQueue\Contracts\MessageQueueContract
     */
    public function connect(array $config)
    {
        // create connection with AMQP
        if (isset($config['ssl_params']) && $config['ssl_params']['ssl_on'] == true) {
            $this->connection = new AMQPSSLConnection(
                $config['host'],
                $config['port'],
                $config['login'],
                $config['password'],
                $config['vhost'],
                $config['ssl']
            );
        } else {
            $this->connection = new AMQPStreamConnection(
                $config['host'],
                $config['port'],
                $config['login'],
                $config['password'],
                $config['vhost'],
                false,
                'AMQPLAIN',
                null,
                'en_US',
                3.0,
                3.0,
                null,
                false,
                120
            );
        }

        return new AMQP(
            $this->connection,
            $config
        );
    }

    public function connection()
    {
        return $this->connection;
    }
}
