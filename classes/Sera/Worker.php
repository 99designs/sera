<?php

/**
 * A worker that monitors a work queue
 */
class Sera_Worker extends Sera_Process
{
	private $_queueFactory;
	private $_queue;

	protected $logger;

	/**
	 * Constructor
	 */
	function __construct(Ergo_Factory $queueFactory)
	{
		$this->logger = Ergo::loggerFor($this);
		$this->_queueFactory = $queueFactory;
	}

	/**
	 * Listens to a particular queue
	 */
	public function listen($queueName)
	{
		$this->logger->info("listening to %s queue", $queueName);
		$this->queue()->listen($queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Process::onStart()
	 */
	protected function onStart()
	{
		$this->logger->info("starting workers...");
	}

	/**
	 * Gets the queue instance
	 */
	protected function queue()
	{
		if(!isset($this->_queue))
		{
			$this->_queue = $this->_queueFactory->create();
		}

		return $this->_queue;
	}

	/**
	 * Template function for executing a task. Handles execution, deletion and release on error.
	 * @throws exception on task failure
	 */
	protected function execute($task, $queue)
	{
		$startTime = microtime(true);
		$this->logger->info("processing task %s", get_class($task));

		$task->execute();
		$queue->delete($task);

		$this->logger->info("task executed in %0.2f seconds",microtime(true)-$startTime);
	}

	/**
	 * Template function for dequeuing a task
	 */
	protected function dequeue($queue)
	{
		return $queue->dequeue();
	}

	/* (non-phpdoc)
	 * @see Sera_Process::main()
	 */
	public function main()
	{
		$queue = $this->queue();
		$task = $this->dequeue($queue);

		// use a custom error handler, chain to the existing loggers
		$errorHandler = new Sera_Task_ErrorHandler($queue, $task);
		$errorHandler->logger()->addLoggers(
			Ergo::application()->errorHandler()->logger()
			);

		Ergo::application()->setErrorHandler($errorHandler);
		$this->execute($task, $queue);
	}
}
