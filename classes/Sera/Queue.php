<?php

/**
 * A queue that can have tasks enqueue and dequeued
 */
interface Sera_Queue
{
	/**
	 * Selects a named queue for publishing events to. Only one queue
	 * can be selected at once. Selecting a queue also listens to it.
	 * @chainable
	 */
	public function select($queueName);

	/**
	 * Selects a named queue for use in dequeue operations. Many queues
	 * can be listened to at once.
	 * @chainable
	 */
	public function listen($queueName);

	/**
	 * Ignores a named queue that was previously listended to.
	 * @chainable
	 */
	public function ignore($queueName);

	/**
	 * Puts a task onto the queue.
	 * @chainable
	 */
	public function enqueue(Sera_Task $task);

	/**
	 * Gets a task from the queue.
	 * @return Sera_Task
	 */
	public function dequeue($timeout=false);

	/**
	 * Deletes a task from the queue.
	 * @chainable
	 */
	public function delete(Sera_Task $task);

	/**
	 * Releases a task back into the queue.
	 * @chainable
	 */
	public function release(Sera_Task $task, $delay=false);
}

