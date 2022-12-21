<?php

/**
 * This is an example of queue connection configuration.
 * It will be merged into config/mq.php.
 * You need to set proper values in `.env`
 */
return [

    'driver'   => 'amqp',

    'host'     => '127.0.0.1',//env('AMQP_HOST', '127.0.0.1'),
    'port'     => '5672',//env('AMQP_PORT', 5672),
    'login'    => 'guest',//env('AMQP_LOGIN', 'guest'),
    'password' => 'guest',//env('AMQP_PASSWORD', 'guest'),
    'vhost'    => '/',//env('AMQP_VHOST', '/'),

    /*
     * The name of default queue.
     */
    'default_queue'    => '',//env('AMQP_QUEUE'),
    'default_exchange' => '',//env('AMQP_EXCHANGE'),

    /*
     * exchange - queue maps
     */
    'route' => [],

    /*
     *  exchange - queue binding
     */
    'binding' => [],

    /*
     * Determine if exchange should be created if it does not exist.
     */
    'exchange_declare' => true,//env('AMQP_EXCHANGE_DECLARE', true),

    /*
     * Determine if queue should be created and binded to the exchange if it does not exist.
     */
    'queue_declare_bind' => true,//env('AMQP_QUEUE_DECLARE_BIND', true),

    /*
     * Read more about possible values at https://www.rabbitmq.com/tutorials/amqp-concepts.html
     */
    'queue_params' => [
        'passive'     => false,// env('AMQP_QUEUE_PASSIVE', false),
        'durable'     => true,// env('AMQP_QUEUE_DURABLE', true),
        'exclusive'   => false,// env('AMQP_QUEUE_EXCLUSIVE', false),
        'auto_delete' => false,// env('AMQP_QUEUE_AUTODELETE', false),
        'arguments'   => '',// env('AMQP_QUEUE_ARGUMENTS'),
    ],
    'exchange_params' => [
        'type'        => 'topic',// env('AMQP_EXCHANGE_TYPE', 'topic'),
        'passive'     => false,// env('AMQP_EXCHANGE_PASSIVE', false),
        'durable'     => true,// env('AMQP_EXCHANGE_DURABLE', true),
        'auto_delete' => false,// env('AMQP_EXCHANGE_AUTODELETE', false),
    ],

    /*
     * Determine the number of seconds to sleep if there's an error communicating with rabbitmq
     * If set to false, it'll throw an exception rather than doing the sleep for X seconds.
     */
    'sleep_on_error' => 5,// env('AMQP_ERROR_SLEEP', 5),

    /*
     * Optional SSL params if an SSL connection is used
     */
    'ssl_params' => [
        'ssl_on'      => false, // env('AMQP_SSL', false),
        'cafile'      => null, // env('AMQP_SSL_CAFILE', null),
        'local_cert'  => null, // env('AMQP_SSL_LOCALCERT', null),
        'verify_peer' => true, // env('AMQP_SSL_VERIFY_PEER', true),
        'passphrase'  => null, // env('AMQP_SSL_PASSPHRASE', null),
    ],

    // debug model
    'debug' => false, // env('APP_DEBUG', false),
];
