<?php

/**
 * A simple queue that uses an PHP array
 */
class Sera_Queue_ArrayQueue implements Sera_Queue
{
	private $_selected;
	private $_listening=array();
	private $_queues=array();

	/* (non-phpdoc)
	 * @see Queue::select
	 */
	public function select($queueName)
	{
		if (!isset($this->_queues[$queueName]))
			$this->_queues[$queueName] = array();
		$this->_selected = $queueName;
		$this->listen($queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::listen
	 */
	public function listen($queueName)
	{
		$this->_listening[$queueName] = true;
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::ignore
	 */
	public function ignore($queueName)
	{
		unset($this->_listening[$queueName]);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::enqueue
	 */
	public function enqueue(Sera_Task $task)
	{
		$this->_queues[$this->_selected][] = $task;
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::dequeue
	 */
	public function dequeue($timeout=false)
	{
		foreach($this->_listening as $queueName=>$false)
		{
			if (isset($this->_queues[$queueName]) &&
				count($this->_queues[$queueName]) != 0)
			{
				return array_shift($this->_queues[$queueName]);
			}
		}

		return null;
	}

	/* (non-phpdoc)
	 * @see Queue::delete
	 */
	public function delete(Sera_Task $task)
	{
		if(($idx = array_search($task->toJson(),
			$this->_queues[$this->_selected])) !== false)
		{
			unset($this->_selectedQueue[$idx]);
		}
	}

	/* (non-phpdoc)
	 * @see Queue::release
	 */
	public function release(Sera_Task $task, $delay=false)
	{
		$this->enqueue($task);
	}

	/**
	 * Executes all tasks on the queue and removes them
	 */
	public function executeAll()
	{
		while ($task = $this->dequeue())
		{
			$task->execute();
			$this->delete($task);
		}
	}

	/**
	 * Reset all internal queues.
	 * @chainable
	 */
	public function reset()
	{
		$this->_selected = null;
		$this->_listening = array();
		$this->_queues = array();
		return $this;
	}
}
