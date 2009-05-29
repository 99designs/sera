<?php

/**
 * A queue which executes tasks immediately instead of queueing them.
 * @author Paul Annesley <paul@99designs.com>
 */
class Sera_Queue_ExecutingQueue implements Sera_Queue
{
	/* (non-phpdoc)
	 * @see Queue::select
	 */
	public function select($queueName)
	{
	}

	/* (non-phpdoc)
	 * @see Queue::enqueue
	 */
	public function enqueue(Sera_Task $task)
	{
		$task->execute();
	}

	/* (non-phpdoc)
	 * @see Queue::dequeue
	 */
	public function dequeue()
	{
		throw new Sera_Queue_QueueException(__METHOD__ . ' not implemented');
	}

	/* (non-phpdoc)
	 * @see Queue::delete
	 */
	public function delete(Sera_Task $task)
	{
		throw new Sera_Queue_QueueException(__METHOD__ . ' not implemented');
	}

	/* (non-phpdoc)
	 * @see Queue::release
	 */
	public function release(Sera_Task $task)
	{
		throw new Sera_Queue_QueueException(__METHOD__ . ' not implemented');
	}
}
