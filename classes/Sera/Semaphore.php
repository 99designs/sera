<?php

/**
 * A wrapper around posix semaphores
 */
class Sera_Semaphore
{
	private $_semaphore;
	private $_acquired=false;

	/**
	 * Constructor
	 */
	public function __construct($id, $count=1)
	{
		$this->_semaphore = sem_get($id, $count, 0666, 0);
	}

	public function acquire()
	{
		if(!sem_acquire($this->_semaphore))
		{
			throw new Sera_Exception("failed to acquire semaphore");
		}

		$this->_acquired = true;
	}

	public function release()
	{
		sem_release($this->_semaphore);
		$this->_acquired = false;
	}

	public function isAcquired()
	{
		return $this->_acquired;
	}
}
