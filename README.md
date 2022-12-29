
# 旨在封装php通用rabbitmq sdk开发包，以方便各框架加载引用
该开发包主要解决rabbitmq相关代码的封装操作，实现多mq服务器的连接配置，易于扩展。
解决消息生产、消费等场景存在的消息丢失、消息堆积等异常问题，异常时记录明确的异常原因以方便深层次排查定位相关问题。

## 一、安装
```
composer require burning/php-rabbitmq dev-main
```

## 二、配置

新增配置文件：new_rabbitmq.php，配置如下：
```php
<?php

/**
 * 新 rabbitmq配置文件
 */
$config["new_rabbitmq"]['connections'] = [

    // 默认 tms 配置
    'default' => [
        'driver' => 'amqp',
        'host' => '192.168.71.210',
        'port' => '5672',
        'vhost' => '/tms', // 按业务系统进行区分，给每个业务系统一个独立的虚拟机
        'login' => 'huangshi',
        'password' => 'HUanGshi',
        #=====================================================================
        # 考虑场景通用化，默认启用Topic模式，可兼容Direct模式
        #=====================================================================
        'default_exchange' => 'tms.exchange', // 业务交换机名称
        'default_queue' => 'tms.ordersys.queue', // 业务队列名称，各系统请采用自身的队列
        #=====================================================================
        # exchange - queues maps
        # 支持exchange对应多个queue
        # exchange与queue一对一，则route中的queue可以不填，自动填充default_queue
        # eg: exchange => queue OR exchange => [queue1, queue2, queue3]
        #=====================================================================
        'route' => [
            'tms.exchange' => [
                'tms.ordersys.queue',
                'tms.tracksys.queue',
            ],
        ],

        #=====================================================================
        # exchange - queue binding
        # queue => binding_key
        # 各个队列绑定值 设置为队列名（下划线连接）
        # Topic Exchange – 将路由键和某模式进行匹配。此时队列需要绑定一个模式上。符号“#”匹配一个或多个词，符号“*”匹配不多不少一个词。
        # 因此“audit.#”能够匹配到“audit.irs.corporate”，但是“audit.*” 只会匹配到“audit.irs”
        #=====================================================================
        'binding' => [
            'tms.ordersys.queue' => 'tms_ordersys.#',
            'tms.tracksys.queue' => 'tms_tracksys.#',
        ],
    ],
    
    // 发布/订阅  广播模式
    'default_fanout' => [
        'driver' => 'amqp',
        'host' => '192.168.71.210',
        'port' => '5672',
        'vhost' => '/tms', // 按业务系统进行区分，给每个业务系统一个独立的虚拟机
        'login' => 'huangshi',
        'password' => 'HUanGshi',
        #=====================================================================
        # Fanout Exchange – 不处理路由键。你只需要简单的将队列绑定到该交换机上。发送到交换机的消息都会被转发到与该交换机绑定的所有队列上。
        # 很像子网广播，每台子网内的主机都获得了一份复制的消息。Fanout交换机转发消息是最快的
        #=====================================================================
        'exchange_params' => [
            'type'        => 'fanout',
            'passive'     => false,
            'durable'     => true,
            'auto_delete' => false,
        ],

        'default_exchange' => 'tms.fanout.exchange', // 业务交换机名称
        'default_queue' => 'tms.fanout.queue', // 业务队列名称，各系统请采用自身的队列
        #=====================================================================
        # exchange - queues maps
        # 支持exchange对应多个queue
        # 发布订阅模式，只要有人订阅当前交换机，并把队列绑定到该交换机，则消息会自动转发到当前交换机绑定的所有队列上，供各订阅者使用
        # eg: exchange => queue OR exchange => [queue1, queue2, queue3]
        #=====================================================================
        'route' => [
            'tms.fanout.exchange' => [
                'tms.fanout.queue',
                'tms.fanout.queue2',
            ],
        ],
    ],
];

```

## 三、使用示例
### 生产消息
```php
try {
	// 加载mq配置
	$config = $this->load->config("new_rabbitmq");

	// mq配置
	$queues_config = $config['connections'];
	if (empty($queues_config)){
		throw new Exception("new_rabbitmq 配置为空，请检查rabbitmq配置");
	}

	// 原mq消息
	$mqData = [
		"sku" => "20221229",
		"country_code" => "CN",
		"create_time" => date("Y-m-d H:i:s")
	];

	// 实例化mq对象
	$mqClient = new \Burning\PhpRabbitmq\MQServiceProvider($queues_config);
	$mq = $mqClient->getMqClient();
	// 创建connection
	$mq->connection("default");

	$mq->setModel(\Burning\PhpRabbitmq\Objects\PublishModel::CONFIRM);
	// 绑定消息到 tms_ordersys.# 匹配模式对应的 tms.ordersys.queue 队列上
	$bindingKey = "tms_ordersys.skuSync";
	// 生产消息
	$correlationId = $mq->push($mqData, $bindingKey);
	if (!$correlationId) {
		// 发送消息失败，获取失败原因 $mq->getHandlerCallbackMessage()
		throw new Exception("MQ生产消息失败：".$mq->getHandlerCallbackMessage());
	}
	// 有值则推送消息成功，为空则失败
	var_dump($correlationId);
}catch (Exception $e){
	echo "推送消息异常：".$e->getMessage();
	exit();
}
```

### 消费消息
```php

try {
	// 加载mq配置
	$config = $this->load->config("new_rabbitmq");

	// mq配置
	$queues_config = $config['connections'];
	if (empty($queues_config)){
		throw new Exception("new_rabbitmq 配置为空，请检查rabbitmq配置");
	}

	// 实例化mq对象
	$mqClient = new \Burning\PhpRabbitmq\MQServiceProvider($queues_config);
	$mq = $mqClient->getMqClient();
	// 创建connection
	$channel = $mq->connection("default");

	// 推模式持续订阅消费消息，$callback回调方法需返回true/false，队列才能明确是否删除消息
	$queue = 'tms.ordersys.queue';
	$channel->consume($queue, function(\PhpAmqpLib\Message\AMQPMessage $message){
		// 消息消费失败的逻辑应该在业务层去做记录、预警等提醒开发跟进，不建议重新入队。避免消息产生堆积

		echo $message->getBody() . myEOL();
		// 消费消息，处理业务逻辑
		
		return true; // 回调为true，队列删除消息
	});

	$channel->start();
}catch (Exception $e){
	$msg = '消费消息异常exception queue[' . $queue . '] message : [ ' . $e->getMessage() . ']';

	if ($e instanceof \PhpAmqpLib\Exception\AMQPRuntimeException) {
		// 部分错误发生后直接重启
		// Broken pipe or closed connection
		// missed server heartbeat
	}

	echo $msg;
	// 特殊异常时，退出进程，以便消费者守护进程自动重启；Unacked 的消息，会重新回到队列的头部，变为 Ready。
	exit(1);

}

```

### 发布/订阅消息（发布消息）
订阅者只要将自己的队列名绑定在该default_fanout配置对应的交换机 tms.fanout.exchange 下，即可自动接收到生产者发布的消息
```php
$config = $this->load->config("new_rabbitmq");

// mq配置
$queues_config = $config['connections'];
if (empty($queues_config)){
	throw new Exception("new_rabbitmq 配置为空，请检查rabbitmq配置");
}

$mqClient = new \Burning\PhpRabbitmq\MQServiceProvider($queues_config);
$mq = $mqClient->getMqClient();
$mq->connection("default_fanout");

$data    = [
	'test' => 'test value',
	'msg' => '测试发布订阅消息',
	'date_time'=>date('Y-m-d H:i:s')
];
$correlation_id = $mq->push($data);
var_dump($correlation_id);
```
### 发布/订阅消息（消费订阅消息）
处理逻辑同上面“消费消息”

