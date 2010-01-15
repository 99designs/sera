<?php

/**
 * A worker that relays tasks from one queue to another
 */
class Sera_Queue_RelayQueueWorker extends Sera_AbstractWorker
{
	private $_source, $_destination, $_queueName;

	/**
	 * Constructor
	 */
	function __construct($source, $destination, $queueName)
	{
		$this->logger = Ergo::loggerFor($this);

		// store for later access
		$this->_source = $source;
		$this->_destination = $destination;
		$this->_queueName = $queueName;

		// wire the queues
		$this->_source->listen($queueName);
		$this->_destination->select($queueName);
	}

	/* (non-phpdoc)
	 * @see Sera_Worker
	 */
	public function execute()
	{
		$this->logger->info("waiting for tasks in relay process #%d [%s]...",
			getmypid(),$this->_queueName);

		$task = $this->_source->dequeue();

		$this->setInteruptable(false);
		$this->logger->trace("relaying task %s to %s",get_class($task),$this->_queueName);
		$this->_destination->enqueue($task);
		$this->_source->delete($task);
		$this->setInteruptable(true);

		return self::WORKER_SUCCESS;
	}
}
