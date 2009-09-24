<?php

/**
 * An extensible decorator for a queue
 */
class Sera_Queue_Decorator implements Sera_Queue
{
	protected $_delegate;

	public function __construct($delegate)
	{
		$this->_delegate = $delegate;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::select
	 */
	public function select($queueName)
	{
		$this->_delegate->select($queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::select
	 */
	public function listen($queueName)
	{
		$this->_delegate->listen($queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::select
	 */
	public function ignore($queueName)
	{
		$this->_delegate->ignore($queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::enqueue
	 */
	public function enqueue(Sera_Task $task)
	{
		$this->_delegate->enqueue($task);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::dequeue
	 */
	public function dequeue($timeout=false)
	{
		return $this->_delegate->dequeue($timeout);
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::delete
	 */
	public function delete(Sera_Task $task)
	{
		$this->_delegate->delete($task);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::release
	 */
	public function release(Sera_Task $task, $delay=false)
	{
		$this->_delegate->release($task, $delay);
		return $this;
	}

	/**
	* Dispatch calls to delegate methods
	*/
	public function __call($method,$params)
	{
		$result = call_user_func_array(
			array($this->_delegate,$method),$params);

		return ($result === $this->_delegate) ?
			$this : $result;
	}
}
