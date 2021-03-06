<?php

/**
 * A queue driver for a BeanStalk queue, uses Pheanstalk
 */
class Sera_Queue_BeanstalkQueue implements Sera_Queue
{
	private $_beanstalk, $_server;

	/**
	 * Constructor
	 */
	public function __construct($server)
	{
		$this->_server = $server[0];
		list($host,$port) = explode(':', $server[0]);

		$this->_beanstalk = new Sera_Queue_PheanstalkDecorator(
			new Pheanstalk($host,$port)
		);
	}

	/* (non-phpdoc)
	 * @see Queue::select
	 */
	public function select($queueName)
	{
		$this->_beanstalk->useTube($queueName);
		$this->listen($queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::listen
	 */
	public function listen($queueName)
	{
		$this->_beanstalk->watch($queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::ignore
	 */
	public function ignore($queueName)
	{
		$this->_beanstalk->ignore($queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::enqueue
	 */
	public function enqueue($task)
	{
		$this->_beanstalk->put(
			$task->toJson(),
			$task->getPriority(),
			0,
			$task->getTimeToRelease()
			);

		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::dequeue
	 */
	public function dequeue($timeout=null)
	{
		// TODO: implement the timeout
		if($job = $this->_beanstalk->reserve($timeout))
		{
			$task = Sera_Task_Builder::fromJson($job->getData());
			$task->beanstalkJob = $job;
			return $task;
		}
		else
		{
			return false;
		}
	}

	/* (non-phpdoc)
	 * @see Queue::delete
	 */
	public function delete($task)
	{
		if(!isset($task->beanstalkJob))
		{
			throw new Exception("Failed to find linked beanstalk job");
		}

		$this->_beanstalk->delete($task->beanstalkJob);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::release
	 */
	public function release($task, $delay=false)
	{
		if(!isset($task->beanstalkJob))
		{
			throw new Exception("Failed to find linked beanstalk job");
		}

		$this->_beanstalk->release(
			$task->beanstalkJob,
			$task->getPriority(),
			$delay
			);

		return $this;
	}

	/**
	 * A beanstalk specific way of moving a task to a buried queue for manual execution
	 */
	public function bury($task)
	{
		if(!isset($task->beanstalkJob))
		{
			throw new Exception("Failed to find linked beanstalk job");
		}

		$this->_beanstalk->bury($task->beanstalkJob);
		return $this;
	}

	/**
	 * A beanstalk specific way of getting task stats
	 */
	public function taskStats($task)
	{
		if(!isset($task->beanstalkJob))
		{
			throw new Exception("Failed to find linked beanstalk job");
		}

		$stats = $this->_beanstalk->statsJob($task->beanstalkJob->getId());
		return array_filter(array(
			'id'=>$task->beanstalkJob->getId(),
			'priority'=>$stats['pri'],
			'age'=>$stats['age'],
			'kicks'=>$stats['kicks'],
			'buries'=>$stats['buries'],
			'releases'=>$stats['releases'],
			'timeouts'=>$stats['timeouts'],
			));
	}

	/**
	 * A beanstalk specific way of getting tube stats
	 */
	public function statsTube($tube)
	{
		$stats = $this->_beanstalk->statsTube($tube);
		return array(
			'tube'=>$stats['name'],
			'urgent'=>$stats['current-jobs-urgent'],
			'ready'=>$stats['current-jobs-ready'],
			'reserved'=>$stats['current-jobs-reserved'],
			'delayed'=>$stats['current-jobs-delayed'],
			'buried'=>$stats['current-jobs-buried'],
			);
	}

	public function stats()
	{
		$stats = $this->_beanstalk->stats();
		return array(
			'urgent'=>$stats['current-jobs-urgent'],
			'ready'=>$stats['current-jobs-ready'],
			'reserved'=>$stats['current-jobs-reserved'],
			'delayed'=>$stats['current-jobs-delayed'],
			'buried'=>$stats['current-jobs-buried'],
		);
	}

	/**
	 * Returns the hostname:port of the queue
	 */
	public function server()
	{
		return $this->_server;
	}
}
