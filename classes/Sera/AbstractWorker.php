<?php

/**
 * An abstract implementation of a worker
 */
abstract class Sera_AbstractWorker
	implements Sera_Worker, Ergo_Error_ErrorHandler
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
			$this->_errorHandler = new Ergo_Error_ConsoleErrorHandler(false);
		}

		return $this->_errorHandler;
	}

	// ------------------------------------
	// worker methods

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
	 * @see Ergo_Error_ErrorHandler
	 */
	public function handle($e)
	{
		return $this->delegateErrorHandler()->handle($e);
	}

	/* (non-phpdoc)
	 * @see Ergo_Error_ErrorHandler
	 */
	public function logger()
	{
		return $this->delegateErrorHandler()->logger();
	}

	/* (non-phpdoc)
	 * @see Ergo_Error_ErrorHandler
	 */
	public function context()
	{
		return $this->delegateErrorHandler()->context();
	}
}

