<?php

namespace Burning\PhpRabbitmq;

use Burning\PhpRabbitmq\MessageQueueManager;

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
    /**
     * mq配置
     * @var array
     */
    private $queuesConfig = [];

    public function __construct($queuesConfig)
    {
        if (is_null($this->mq)){
            $this->queuesConfig = $queuesConfig;
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
        $queues_config = $this->queuesConfig;//config('mq.connections');
        $queues_config = $queues_config ?? [];
        $this->app['config']['mqDefaultDriver'] = "amqp";
        foreach ($queues_config as $key => $config) {
            if ($config['driver'] == self::$driver) {
                $amqpConfigPath = __DIR__ . '/config/amqp.php';
                $this->app['config'][$key] = array_merge(require $amqpConfigPath, $config);
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

        $manager = new MessageQueueManager($app);
        $manager->addConnector(self::$driver, function(){
            return new Connectors\AMQPConnector;
        });

        $this->mq = $manager;

        /*$appFunction = function ($app) {
            return $this->tap(new MessageQueueManager($app), function($manager){
                $manager->addConnector(self::$driver, function(){
                    return new Connectors\AMQPConnector;
                });
            });
        };

        $this->mq = $appFunction($app);*/
    }

    public function getMqClient()
    {
        return $this->mq;
    }
}
