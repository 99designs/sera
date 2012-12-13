Sera - A queueing library
=========================

Allows for long-running or asynchronous tasks to be written as Tasks and queued on a variety on queuing mechanism.

Currently supports [Beanstalk](http://kr.github.com/beanstalkd/) and [Amazon SQS](http://aws.amazon.com/sqs/).

[![Build Status](https://travis-ci.org/99designs/sera.png)](https://travis-ci.org/99designs/sera)

Installation
------------

To add sera to a project, the easiest way is via [composer](http://getcomposer.com):

```json
{
    "require": {
        "99designs/sera": ">=1.0.0"
    }
}
```

Usage
-----

```php
<?php

// a simple task
class MyTask extends Sera_Task_Abstract
{
	public static function create($params)
	{
		return new self($params);
	}

	public function execute()
	{
    my_long_running_function($this->_data);
	}
}

// a queue that connects to beanstalkd
$queue = new Sera_Queue_BeanstalkQueue("127.0.0.1");
$queue->select('llama_tasks');

// enqueue the task
$queue->enqueue(MyTask::create('some data'));

// normally this would be in a seperate process
$worker = new Sera_Queue_QueueWorker($queue);
$worker->execute();
```

Copyright
---------

Copyright (c) 2012 99designs See [LICENSE](https://github.com/99designs/sera/blob/master/LICENSE) for details.

