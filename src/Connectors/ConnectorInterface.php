<?php

namespace Burning\PhpRabbitmq\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Burning\PhpRabbitmq\Connectors\Queue
     */
    public function connect(array $config);
}
