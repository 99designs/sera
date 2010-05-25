<?php

/**
 * A console logger that includes process information and allows for semaphore locking
 *
 * @author Lachlan Donald <lachlan@99designs.com>
 */
class Sera_Logging_SerializedLogger extends Ergo_Logging_ConsoleLogger
{
	private $_semaphore;

	protected function _getMessageFormat()
	{
		return "[process #".getmypid()." ".date("Y-m-d H:i:s")." %s] %s :: %s\n";
	}

	/* (non-phpdoc)
	 * @see Ergo_Logger::log()
	 */
	public function log($message,$level=Ergo_Logger::INFO)
	{
		if(!$this->_semaphore || ($this->_semaphore && $this->_semaphore->isAcquired()))
		{
			parent::log($message, $level);
		}
		else if($this->_semaphore)
		{
			$this->_semaphore->acquire();
			parent::log($message, $level);
			$this->_semaphore->release();
		}
	}

	/**
	 * Use a semaphore to serialize console output
	 * @param resource a resource returned from {@link sem_get()}
	 * @see sem_get()
	 * @see sem_acquire()
	 */
	public function setSemaphore($semaphore)
	{
		$this->_semaphore = $semaphore;
	}
}
