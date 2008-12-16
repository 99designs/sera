<?php

/**
 * A queue driver for a BeanStalk queue, uses Pheanstalk
 */
class Sera_Queue_BeanstalkQueue implements Sera_Queue
{
	private $_beanstalk;

	public function __construct($servers)
	{
		// TODO: support more than one server
		list($host,$port) = explode(':', $servers[0]);

		$this->_beanstalk = new Pheanstalk_Connection($host,$port);
	}

	/* (non-phpdoc)
	 * @see Queue::select
	 */
	public function select($queueName)
	{
		// beanstalk watches multiple queues, but submits to one
		$this->_beanstalk->useTube($queueName);
		$this->_beanstalk->watchTube($queueName);

		// unwatch queues other than the selected
		foreach($this->_beanstalk->getWatchedTubes() as $watched)
		{
			if($queueName != $watched)
				$this->_beanstalk->ignoreTube($watched);
		}

		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::enqueue
	 */
	public function enqueue(Sera_Task $task)
	{
		$this->_beanstalk->put($task->toJson());

		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::dequeue
	 */
	public function dequeue()
	{
		$job = $this->_beanstalk->reserve();
		$task = Sera_Task_Builder::fromJson($job->getData());
		$task->beanstalkJob = $job;
		return $task;
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
	public function release(Sera_Task $task)
	{
		if(!isset($task->beanstalkJob))
		{
			throw new Commerce_Exception("Failed to find linked beanstalk job");
		}

		$this->_beanstalk->release($task->beanstalkJob);
		return $this;
	}
}

?>
