<?php

/**
 * An abstract implementation of a worker
 */
abstract class Sera_AbstractWorker
	implements Sera_Worker, \Ergo\Error\ErrorHandler
{
	private $_interuptable=true;
	private $_errorHandler;

	/**
	 * Returns a console error handler
	 */
	protected function delegateErrorHandler()
	{
		if(!isset($this->_errorHandler))
		{
			$this->_errorHandler = new \Ergo\Error\ConsoleErrorHandler(false);
		}

		return $this->_errorHandler;
	}

	// ------------------------------------
	// worker methods

	/* (non-phpdoc)
	 * @see Sera_Worker
	 */
	public function terminate()
	{
		exit(Sera_WorkerFarm::SPAWN_TERMINATE);
	}

	/* (non-phpdoc)
	 * @see Sera_Worker
	 */
	function getErrorHandler()
	{
		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Worker
	 */
	function isInteruptable()
	{
		return $this->_interuptable;
	}

	/**
	 * Sets whether the worker can be interupted
	 */
	protected function setInteruptable($bool)
	{
		$this->_interuptable = (bool) $bool;
	}

	// ------------------------------------
	// error handler methods

	/* (non-phpdoc)
	 * @see \Ergo\Error\ErrorHandler
	 */
	public function handle($e)
	{
		return $this->delegateErrorHandler()->handle($e);
	}

	/* (non-phpdoc)
	 * @see \Ergo\Error\ErrorHandler
	 */
	public function logger()
	{
		return $this->delegateErrorHandler()->logger();
	}
}

