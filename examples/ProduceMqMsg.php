<?php


// 生产消息
produceMessage();


// 发布/订阅 消息（广播模式）
fanoutMessage();


/**
 * 生产消息
 */
function produceMessage()
{
	try {
		// 加载mq配置
		$config = getRabbitMqServerConfig();
		// mq配置
		$queuesConfig = $config['connections'];

		// 原mq消息
		$mqData = [
			"sku" => "20221229",
			"country_code" => "CN",
			"create_time" => date("Y-m-d H:i:s")
		];

		// 实例化mq对象
		$mqClient = new \Burning\PhpRabbitmq\MQServiceProvider($queuesConfig);
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

}


/**
* 发布/订阅 消息（广播模式）
*/
function fanoutMessage()
{
	// 加载mq配置
	$config = getRabbitMqServerConfig();
	// mq配置
	$queuesConfig = $config['connections'];

	$mqClient = new \Burning\PhpRabbitmq\MQServiceProvider($queues_config);
	$mq = $mqClient->getMqClient();
	$mq->connection("default_fanout");
	// 可根据情况确认是否启用消息发布模式
//        $mq->setModel(\Burning\PhpRabbitmq\Objects\PublishModel::CONFIRM);
	$data    = [
		'test' => 'test value',
		'msg' => '测试发布订阅消息',
		'date_time'=>date('Y-m-d H:i:s')
	];
	$correlation_id = $mq->push($data);
	var_dump($correlation_id);

}

/**
* 返回rabbitmq 服务器配置配置
*/
function getRabbitMqServerConfig(){
	$config = [];		
	$config['connections'] = [

		// 默认 tms 配置
		'default' => [
			'driver' => 'amqp',
			'host' => '192.168.71.210',
			'port' => '5672',
			'vhost' => '/', // 虚拟主机（各业务系统独立，按生产者区分）
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
	];
	
	return $config;
}