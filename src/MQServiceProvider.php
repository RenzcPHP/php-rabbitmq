<?php

namespace Burning\PhpRabbitmq;

use Rex\MessageQueue\MessageQueueManager;

class MQServiceProvider
{
    protected static $driver = 'amqp';

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * MQ 实例
     * @var null
     */
    private $mq = null;

    public function __construct($connection)
    {
        if (is_null($this->mq)){
            $this->init();
        }
    }

    protected function init()
    {
        $this->register();

        $this->boot();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $queues_config = config('mq.connections');
        $queues_config = $queues_config ?? [];
        $this->app['config']['mqDefaultDriver'] = "amqp";
        foreach ($queues_config as $key => $config) {
            if ($config['driver'] == self::$driver) {
                $this->app['config'][$key] = $config;
            }
        }
    }

    private function tap($value, $callback)
    {
        $callback($value);

        return $value;
    }

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {
        $app = $this->app;

        $appFunction = function ($app) {
            return $this->tap(new MessageQueueManager($app), function($manager){
                $manager->addConnector(self::$driver, function(){
                    return new Connectors\AMQPConnector;
                });
            });
        };

        $this->mq = $appFunction($app);

        /*
        $this->app->singleton('mq', function ($app) {
            return tap(new MessageQueueManager($app), function($manager){
                $manager->addConnector(self::$driver, function(){
                    return new Connectors\AMQPConnector;
                });
            });
        });*/

    }
}
