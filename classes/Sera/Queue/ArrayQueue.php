<?php

/**
 * A simple queue that uses an PHP array
 */
class Sera_Queue_ArrayQueue implements Sera_Queue
{
	protected $_selected;
	protected $_listening=array();
	protected $_queues=array();

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
	public function enqueue($task)
	{
		$this->_queues[$this->_selected][] = $task->toJson();
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
				return Sera_Task_Builder::fromJson(
					array_shift($this->_queues[$queueName])
				);
			}
		}

		return null;
	}

	/* (non-phpdoc)
	 * @see Queue::delete
	 */
	public function delete($task)
	{
		if(isset($this->_queues[$this->_selected]) &&
			($idx = array_search($task->toJson(),
			$this->_queues[$this->_selected])) !== false)
		{
			unset($this->_selectedQueue[$idx]);
		}
	}

	/* (non-phpdoc)
	 * @see Queue::release
	 */
	public function release($task, $delay=false)
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
		}
	}

	/**
	 * Checks whether the queue contains a particular task via
	 * a callback that takes a task and returns true or false.
	 */
	public function contains($callback)
	{
		foreach($this->_queues as $name=>$queue)
		{
			foreach($queue as $task)
			{
				if(call_user_func($callback,$task)) return true;
			}
		}

		return false;
	}

	/**
	 * Copy tasks in this queue to another queue
	 */
	public function copyTo($destQueue)
	{
		foreach($this->_queues as $name => $queue)
		{
			$destQueue->select($name);

			foreach($queue as $task)
			{
				$task = Sera_Task_Builder::fromJson($task);
				$destQueue->enqueue($task);
			}
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
