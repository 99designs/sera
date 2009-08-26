<?php

/**
 * A worker that monitors a work queue
 */
class Sera_Worker extends Sera_Process
{
	private $_queueFactory;
	private $_listen=array();

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
		$this->_listen[$queueName] = $queueName;
		return $this;
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

		$this->logger->trace("task executed in %0.2f seconds",microtime(true)-$startTime);
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
		$queue = $this->_queueFactory->create();
		foreach($this->_listen as $listen) $queue->listen($listen);

		$this->logger->trace("waiting for tasks in child #%d...", getmypid());
		$task = $this->dequeue($queue);

		// use a custom error handler, chain to the existing loggers
		$errorHandler = new Sera_Task_ErrorHandler($queue, $task);
		$errorHandler->logger()->addLoggers(
			Ergo::application()->errorHandler()->logger()
			);

		Ergo::application()->setErrorHandler($errorHandler);
		$this->execute($task, $queue);
	}

	/* (non-phpdoc)
	 * @see Sera_Process::spawn()
	 */
	public function spawn($parallel=1)
	{
		$this->logger->trace("spawning %d workers...", $parallel);
		return parent::spawn($parallel);
	}
}
