<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default Queue Driver
	|--------------------------------------------------------------------------
	|
	| The Laravel queue API supports a variety of back-ends via an unified
	| API, giving you convenient access to each back-end using the same
	| syntax for each one. Here you may set the default queue driver.
	|
	| Supported: "null", "sync", "database", "beanstalkd",
	|            "sqs", "iron", "redis"
	|
	*/

	'default' => env('QUEUE_DRIVER', 'sync'),

	/*
	|--------------------------------------------------------------------------
	| Queue Connections
	|--------------------------------------------------------------------------
	|
	| Here you may configure the connection information for each server that
	| is used by your application. A default configuration has been added
	| for each back-end shipped with Laravel. You are free to add more.
	|
	*/

	'connections' => [

		'sync' => [
			'driver' => 'sync',
		],

		'database' => [
			'driver' => 'database',
			'table' => 'jobs',
			'queue' => 'default',
			'expire' => 60,
		],

		'webhook_xcaller' => [
			'driver' => 'beanstalkd',
			'host'   => env('WEBHOOK_XCALLER_HOST', 'localhost'),
			'port'   => env('WEBHOOK_XCALLER_PORT', 11300),
			'queue'  => 'default',
			'ttr'    => 60,
		],

		'webhook_xcaller_testing' => [
			'driver' => 'webhook_xcaller_testing',
			'queue'  => 'default',
		],

		'rabbitmq-signal' => [

				'driver' => 'rabbitmq',

				'dsn' => env('RABBITMQ_DSN', null),

				'connection' => PhpAmqpLib\Connection\AMQPLazyConnection::class,
				'queue' => env('RABBITMQ_SIGNAL_QUEUE', 'default'),
				'hosts' => [
					[
						'host' => env('RABBITMQ_HOST', '127.0.0.1'),
						'port' => env('RABBITMQ_PORT', 5672),
						'vhost' => env('RABBITMQ_SIGNAL_VHOST', '/'),
						'user' => env('RABBITMQ_SIGNAL_LOGIN', 'guest'),
						'password' => env('RABBITMQ_SIGNAL_PASSWORD', 'guest'),
					],
				],
				'options' => [

						'exchange' => [

								'name' => env('RABBITMQ_EXCHANGE_NAME'),

								/*
								* Determine if exchange should be created if it does not exist.
								*/
								'declare' => env('RABBITMQ_EXCHANGE_DECLARE', true),

								/*
								* Read more about possible values at https://www.rabbitmq.com/tutorials/amqp-concepts.html
								*/
								'type' => env('RABBITMQ_EXCHANGE_TYPE', 'direct'),
								'passive' => env('RABBITMQ_EXCHANGE_PASSIVE', false),
								'durable' => env('RABBITMQ_EXCHANGE_DURABLE', true),
								'auto_delete' => env('RABBITMQ_EXCHANGE_AUTODELETE', false),
						],

						'queue' => [

								/*
								* The name of default queue.
								*/
								'name' => env('RABBITMQ_SIGNAL_QUEUE', 'default'),

								/*
								* Determine if queue should be created if it does not exist.
								*/
								'declare' => env('RABBITMQ_QUEUE_DECLARE', true),

								/*
								* Determine if queue should be binded to the exchange created.
								*/
								'bind' => env('RABBITMQ_QUEUE_DECLARE_BIND', true),

								/*
								* Read more about possible values at https://www.rabbitmq.com/tutorials/amqp-concepts.html
								*/
								'passive' => env('RABBITMQ_QUEUE_PASSIVE', false),
								'durable' => env('RABBITMQ_QUEUE_DURABLE', true),
								'exclusive' => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
								'auto_delete' => env('RABBITMQ_QUEUE_AUTODELETE', false),
								'arguments' => env('RABBITMQ_QUEUE_ARGUMENTS'),

						],

				],

				/*
				 * Determine the number of seconds to sleep if there's an error communicating with rabbitmq
				 * If set to false, it'll throw an exception rather than doing the sleep for X seconds.
				 */
				'sleep_on_error' => env('RABBITMQ_ERROR_SLEEP', 5),

				/*
				 * Optional SSL params if an SSL connection is used
				 */
				'ssl_params' => [
						'ssl_on' => env('RABBITMQ_SSL', false),
						'cafile' => env('RABBITMQ_SSL_CAFILE', null),
						'local_cert' => env('RABBITMQ_SSL_LOCALCERT', null),
						'local_key' => env('RABBITMQ_SSL_LOCALKEY', null),
						'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
						'passphrase' => env('RABBITMQ_SSL_PASSPHRASE', null),
				],

		],

	],

	/*
	|--------------------------------------------------------------------------
	| Failed Queue Jobs
	|--------------------------------------------------------------------------
	|
	| These options configure the behavior of failed queue job logging so you
	| can control which database and table are used to store the jobs that
	| have failed. You may change them to any database / table you wish.
	|
	*/

	'failed' => [
		'database' => 'mysql', 'table' => 'failed_jobs',
	],

];
