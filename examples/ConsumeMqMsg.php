<?php


//消费消息 —— 推模式
comsumerMsgPush();

//消费消息 —— 拉模式
consumeMsgPull();


/**
 * 消费消息 —— 推模式
 */
function comsumerMsgPush()
{
	try {
		// 加载mq配置
		$config = getRabbitMqServerConfig();
		// mq配置
		$queuesConfig = $config['connections'];

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
			// todo 消费消息，处理业务逻辑

			return true; // 回调为true，队列删除消息
		});

		$channel->start();
	}catch (Exception $e){
		$msg = '推模式消费消息异常exception queue[' . $queue . '] message : [ ' . $e->getMessage() . ']';

		echo $msg;
		// 特殊异常时，退出进程，以便消费者守护进程自动重启；Unacked 的消息，会重新回到队列的头部，变为 Ready。
		exit(1);

	}

}

/**
 * 消费消息 —— 拉模式
 */
protected function consumeMsgPull()
{
	try {
		// 加载mq配置
		$config = getRabbitMqServerConfig();
		// mq配置
		$queuesConfig = $config['connections'];
		
		$queue = 'tms.ordersys.queue';
		// 实例化mq对象
		$mqClient = new \Burning\PhpRabbitmq\MQServiceProvider($queues_config);
		$mq = $mqClient->getMqClient();
		// 创建connection
		$channel = $mq->connection("default");

		#=================================================
		#  消费者单条地获取消息，多并发下可能出现意外情况
		#  此模式影响RabbitMQ性能。在意高吞吐量，建议用推模式
		#  $queue可以为空，默认取config.php中的defaultQueue
		#=================================================
		while ($channel->size() > 0) {
			try {
				$message = $channel->pop();
				$dataArr = json_decode($message->getBody(), true);
				// 调试可打印查看消息数据结构
				var_dump($dataArr);

				if (!empty($dataArr)){
					// todo 处理业务逻辑，处理成功后ack，失败则抛异常
					$channel->ack();
				}
			} catch (\Exception $e) {
				echo $e->getMessage();

				// 消息消费失败，拒绝消息，避免消息重新回到队列的头部，一直消费失败阻塞消息队列，造成消息堆积
				$channel->reject();
				// todo 记录异常日志，根据业务重要程度决定是否要钉钉告警
			}
		}
	}catch (Exception $e){
		// 队列异常，钉钉提醒
		$msg = '拉模式消费消息异常exception queue[' . $queue . '] message : [ ' . $e->getMessage() . ']';
		echo $msg;
		// 特殊异常时，退出进程，以便消费者守护进程自动重启；Unacked 的消息，会重新回到队列的头部，变为 Ready。
		exit(1);
	}
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