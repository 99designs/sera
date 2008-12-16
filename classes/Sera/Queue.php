<?php

/**
 * A queue that can have tasks enqueue and dequeued
 */
interface Sera_Queue
{
	/**
	 * Selects a named subqueue for use in subsequent operations
	 */
	function select($queueName);

	/**
	 * Puts a task onto the queue
	 */
	function enqueue(Sera_Task $task);

	/**
	 * Gets a task from the queue
	 */
	function dequeue();

	/**
	 * Deletes a task from the queue
	 */
	function delete(Sera_Task $task);

	/**
	 * Releases a task back into the queue
	 */
	function release(Sera_Task $task);
}

?>
