<?php

/**
 * A simple queue that uses an PHP array
 */
class Sera_Queue_ArrayQueue implements Sera_Queue
{
	private $_selectedQueue;
	private $_queue=array();

	/* (non-phpdoc)
	 * @see Queue::select
	 */
	public function select($queueName)
	{
		if(!isset($this->_queue[$queueName]))
		{
			$this->_queue[$queueName] = array();
		}

		$this->_selectedQueue =& $this->_queue[$queueName];
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::enqueue
	 */
	public function enqueue(Sera_Task $task)
	{
		array_push(
			$this->_selectedQueue,
			$task->toJson()
		);
	}

	/* (non-phpdoc)
	 * @see Queue::dequeue
	 */
	public function dequeue()
	{
		if (count($this->_selectedQueue) == 0)
			return null;

		return Sera_Task_Builder::fromJson(
			array_shift($this->_selectedQueue)
		);
	}

	/* (non-phpdoc)
	 * @see Queue::delete
	 */
	public function delete(Sera_Task $task)
	{
		if(($idx = array_search($task->toJson(), $this->_selectedQueue)) !== false)
		{
			unset($this->_selectedQueue[$idx]);
		}
	}

	/* (non-phpdoc)
	 * @see Queue::release
	 */
	public function release(Sera_Task $task)
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

}
