<?php

/**
 * A worker that monitors a work queue
 */
class Sera_WorkerFarm extends Sera_Process
{
	private $_worker;
	private $_terminate=false;
	private $_logger;

	/**
	 * Constructor
	 */
	public function __construct(Sera_Worker $worker)
	{
		$this->_logger = Ergo::loggerFor($this);
		$this->_worker = $worker;
	}

	/* (non-phpdoc)
	 * @see Sera_Process::main()
	 */
	public function main()
	{
		// use a custom error handler, chain to the existing loggers
		$errorHandler = $this->_worker->getErrorHandler();
		$errorHandler->logger()->addLoggers(
			Ergo::application()->errorHandler()->logger()
			);

		Ergo::application()->setErrorHandler($errorHandler);
		return $this->_worker->execute();
	}

	/* (non-phpdoc)
	 * @see Sera_Process::spawn()
	 */
	public function spawn($count=1)
	{
		$this->_logger->trace("spawning %d workers...", $count);
		return parent::spawn($count);
	}

	/* (non-phpdoc)
	 * @see Sera_Process::spawn()
	 */
	public function signal($signo)
	{
		if($this->getParentPid())
		{
			if($this->isSignalTerminate($signo))
			{
				// force a terminate on a double signal
				if($this->_terminate)
				{
					$this->_logger->trace("forcing worker termination");
					$this->onTerminate(self::SPAWN_TERMINATE);
					exit(self::SPAWN_TERMINATE);
				}
				// other wise terminate immediately if interuptable
				else if($this->_worker->isInteruptable())
				{
					$this->_logger->trace("shutting down");
					$this->onTerminate(self::SPAWN_TERMINATE);
					exit(self::SPAWN_TERMINATE);
				}
				// otherwise wait for the children to finish
				else
				{
					$this->_logger->trace("waiting for child to finish work before terminating");
					$this->_terminate = true;
				}
			}
		}
		else
		{
			parent::signal($signo);
		}
	}
}
