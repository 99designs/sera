<?php

/**
 * A queue driver for a BeanStalk queue, uses Pheanstalk
 */
class Sera_Queue_BeanstalkQueue implements Sera_Queue
{
	/**
	 * Released tasks are given a low priority to put them at
	 * the back of the queue.
	 */
	const RELEASE_PRIORITY = 50;

	/**
	 * Released tasks are delayed to prevent thrashing.
	 */
	const RELEASE_DELAY = 10;

	private $_beanstalk;

	public function __construct($servers)
	{
		// TODO: support more than one server
		list($host,$port) = explode(':', $servers[0]);

		$this->_beanstalk = new Sera_Queue_PheanstalkDecorator(
			new Pheanstalk($host,$port)
		);
	}

	/* (non-phpdoc)
	 * @see Queue::select
	 */
	public function select($queueName)
	{
		// beanstalk watches multiple queues, but submits to one
		$this->_beanstalk->useTube($queueName);
		$this->_beanstalk->watch($queueName);

		// unwatch queues other than the selected
		foreach($this->_beanstalk->listTubesWatched() as $watched)
		{
			if($queueName != $watched)
				$this->_beanstalk->ignore($watched);
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

		$this->_beanstalk->release(
			$task->beanstalkJob,
			self::RELEASE_PRIORITY,
			self::RELEASE_DELAY);

		return $this;
	}
}

?>
