<?php

/**
 * A worker that monitors a work queue
 */
class Sera_Worker extends Sera_Process implements Ergo_ExceptionStrategy
{
	private $_executeTasks=true;
	private $_queueFactory;
	private $_queue;

	protected $queueName;
	protected $logger;

	function __construct(Ergo_Factory $queueFactory, $queueName)
	{
		$this->logger = Ergo::loggerFor($this);
		$this->queueName = $queueName;
		$this->_queueFactory = $queueFactory;
	}

	/**
	 * Sets whether tasks should be executed
	 */
	public function setExecuteTasks($value)
	{
		$this->_executeTasks = $value;
	}

	/* (non-phpdoc)
	 * @see Sera_Process::onStart()
	 */
	protected function onStart()
	{
		$this->logger->info("listening to %s queue...", $this->queueName);
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

	/* (non-phpdoc)
	 * @see Sera_Process::main()
	 */
	public function main()
	{
		$queue = $this->queue()->select($this->queueName);
		$task = $queue->dequeue();
		$this->logger->info("processing task %s", get_class($task));
		$startTime = microtime(true);

		try
		{
			if($this->_executeTasks)
			{
				$task->execute();
				$queue->delete($task);
				$this->logger->info("task completed in %0.2f seconds",
					microtime(true) - $startTime);
			}
			else
			{
				$queue->delete($task);
				$this->logger->info("task removed without execution");
			}
		}
		catch(Exception $e)
		{
			$queue->release($task);
			throw $e;
		}
	}

	/**
	 * Handle an uncaught exception
	 */
	public function handleException($e)
	{
		Ergo::application()->errorHandler()->logException($e);
		$this->logger->logException($e);
	}
}
