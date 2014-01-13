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

	// api for parameters set by the worker farm
	public $_spawn_id=1;
	public $_spawn_config=array();

	/**
	 * Constructor
	 * @param $queue object either queue instance or an \Ergo\Factory
	 * @param $logger a logger to write output to
	 */
	function __construct($queue, $queueName=null, $logger=null)
	{
		$this->logger = $logger ?: new Ergo\Logging\NullLogger();

		if($queue instanceof \Ergo\Factory)
		{
			$this->_queueFactory = $queue;
		}
		else
		{
			$this->_queue = $queue;
		}

		if(!is_null($queueName))
		{
			$queueName = is_array($queueName) ? $queueName : array($queueName);

			foreach($queueName as $name) $this->listen($name);
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
		$this->logger->info(sprintf("processing task %s", get_class($task)));

		$task->execute();
		$queue->delete($task);

		$this->logger->info(sprintf("task executed in %0.2f seconds",microtime(true)-$startTime));
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

		$this->logger->info(sprintf(
			"waiting for tasks in process #%d (worker %d) [%s]...",
			getmypid(),
			$this->_spawn_id,
			implode(',', $this->_listen)
		));

		if(!$this->_lastTask = $this->_queue->dequeue())
		{
			$this->logger->debug(sprintf("dequeue timed out (worker %d)", $this->_spawn_id));
			return self::WORKER_FAILURE;
		}

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
			if($this->getLastTask())
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
			$this->logger()->error($re->getMessage(), array('exception' => $re));
		}

		parent::handle($e);
	}

	/**
	 * Returns the current queue
	 */
	protected function getQueue()
	{
		return $this->_queue;
	}

	/**
	 * Returns the last task executed, or false if no tasks have been executed
	 */
	protected function getLastTask()
	{
		if(!$this->_lastTask)
		{
			throw new Sera_Exception("The queue worker has no last task");
		}

		return $this->_lastTask;
	}
}
