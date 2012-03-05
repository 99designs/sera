<?php

/**
 * A queue driver for a BeanStalk queue, uses Pheanstalk
 */
class Sera_Queue_BeanstalkQueue implements Sera_Queue
{
	private $_beanstalk;

	/**
	 * Constructor
	 */
	public function __construct($server)
	{
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
	public function enqueue(Sera_Task $task)
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
	public function delete(Sera_Task $task)
	{
		if(!isset($task->beanstalkJob))
		{
			throw new Commerce_Exception("Failed to find linked beanstalk job");
		}

		$this->_beanstalk->delete($task->beanstalkJob);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::release
	 */
	public function release(Sera_Task $task, $delay=false)
	{
		if(!isset($task->beanstalkJob))
		{
			throw new Commerce_Exception("Failed to find linked beanstalk job");
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
	public function bury(Sera_Task $task)
	{
		if(!isset($task->beanstalkJob))
		{
			throw new Commerce_Exception("Failed to find linked beanstalk job");
		}

		$this->_beanstalk->bury($task->beanstalkJob);
		return $this;
	}

	/**
	 * A beanstalk specific way of getting task stats
	 */
	public function taskStats(Sera_Task $task)
	{
		if(!isset($task->beanstalkJob))
		{
			throw new Commerce_Exception("Failed to find linked beanstalk job");
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
}
