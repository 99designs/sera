<?php

/**
 * A worker that monitors a work queue
 */
class Sera_Queue_QueueWorker extends Sera_AbstractWorker
{
	const RELEASE_DELAY=10; // 10 seconds

	private $_queueFactory;
	private $_queue;
	private $_listen=array();
	private $_lastTask=false;
	protected $logger;

	/**
	 * Constructor
	 * @param $queue object either queue instance or an Ergo_Factory
	 */
	function __construct($queue, $queueName=null)
	{
		$this->logger = Ergo::loggerFor($this);

		if($queue instanceof Ergo_Factory)
		{
			$this->_queueFactory = $queue;
		}
		else
		{
			$this->_queue = $queue;
		}

		if(!is_null($queueName))
		{
			$this->listen($queueName);
		}
	}

	/**
	 * Listens to a particular queue
	 */
	public function listen($queueName)
	{
		$this->_listen[$queueName] = $queueName;
		if(isset($this->_queue)) $this->_queue->listen($queueName);
		return $this;
	}

	/**
	 * Template function for executing a task. Handles execution, deletion and release on error.
	 * @throws exception on task failure
	 */
	protected function executeTask($task, $queue)
	{
		$startTime = microtime(true);
		$this->logger->info("processing task %s", get_class($task));

		$task->execute();
		$queue->delete($task);

		$this->logger->info("task executed in %0.2f seconds",microtime(true)-$startTime);
	}


	/* (non-phpdoc)
	 * @see Sera_Worker
	 */
	public function execute()
	{
		if(!isset($this->_queue))
		{
			$this->_queue = $this->_queueFactory->create();
			foreach($this->_listen as $listen) $this->_queue->listen($listen);
		}

		$this->logger->trace("waiting for tasks in child #%d (worker %d) [%s]...",
			getmypid(), $this->_spawn_id, implode(',', $this->_listen));

		$this->_lastTask = $this->_queue->dequeue();

		$this->setInteruptable(false);
		$this->executeTask($this->_lastTask, $this->_queue);
		$this->setInteruptable(true);

		return self::WORKER_SUCCESS;
	}

	/* (non-phpdoc)
	 * @see Sera_Worker::handle($e)
	 */
	public function handle($e)
	{
		try
		{
			if($this->_lastTask)
			{
				$this->logger()->error(
					"worker terminated with an uncaught error, releasing task for %d seconds",
						self::RELEASE_DELAY
						);

				$this->_queue->release($this->_lastTask, self::RELEASE_DELAY);
			}
		}
		catch(Exception $re)
		{
			$this->logger()->logException($re);
		}

		parent::handle($e);
	}
}
