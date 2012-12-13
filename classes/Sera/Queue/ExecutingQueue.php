<?php

/**
 * A queue which executes tasks immediately instead of queueing them.
 */
class Sera_Queue_ExecutingQueue implements Sera_Queue
{
	/* (non-phpdoc)
	 * @see Queue::select
	 */
	public function select($queueName)
	{
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::listen
	 */
	public function listen($queueName)
	{
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::ignore
	 */
	public function ignore($queueName)
	{
		return $this;
	}

	/* (non-phpdoc)
	 * @see Queue::enqueue
	 */
	public function enqueue($task)
	{
		$task->execute();
	}

	/* (non-phpdoc)
	 * @see Queue::dequeue
	 */
	public function dequeue($timeout=false)
	{
		throw new Sera_Queue_QueueException(__METHOD__ . ' not implemented');
	}

	/* (non-phpdoc)
	 * @see Queue::delete
	 */
	public function delete($task)
	{
		throw new Sera_Queue_QueueException(__METHOD__ . ' not implemented');
	}

	/* (non-phpdoc)
	 * @see Queue::release
	 */
	public function release($task, $delay=false)
	{
		throw new Sera_Queue_QueueException(__METHOD__ . ' not implemented');
	}
}
