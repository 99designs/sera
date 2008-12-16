<?php

/**
 * A task which can be executed (command pattern) and also serialized
 * for later execution on a queue
 */
interface Sera_Task
{
	/**
	 * Executes the task
	 */
	public function execute();

	/**
	 * Returns the version of the task
	 */
	public function getVersion();

	/**
	 * Public access to the task data.
	 * return array;
	 */
	public function getData();

	/**
	 * Serializes the task and it's data to json
	 */
	public function toJson();

	/**
	 * Creates a Task instance from a json snippet
	 * @return Commerce_Queue_Task
	 */
	public static function fromJson($json);
}

