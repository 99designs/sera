<?php

/**
 * A queue that can have tasks enqueue and dequeued
 */
interface Sera_Queue
{
	/**
	 * Selects a named subqueue for use in subsequent operations
	 * @chainable
	 */
	public function select($queueName);

	/**
	 * Puts a task onto the queue
	 * @chainable
	 */
	public function enqueue(Sera_Task $task);

	/**
	 * Gets a task from the queue
	 * @return Sera_Task
	 */
	public function dequeue();

	/**
	 * Deletes a task from the queue
	 * @chainable
	 */
	public function delete(Sera_Task $task);

	/**
	 * Releases a task back into the queue
	 * @chainable
	 */
	public function release(Sera_Task $task);
}

?>
