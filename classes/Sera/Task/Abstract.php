<?php

/**
 * A task which can be executed (command pattern) and also have it's internal
 * data serialized into JSON
 */
abstract class Sera_Task_Abstract implements Sera_Task
{
	protected $_data;
	protected $_signature=false;

	/**
	 * Constructs a task
	 */
	protected function __construct($data)
	{
		$this->_data = $data;
	}

	/**
	 * Returns the version of the task
	 */
	public function getVersion()
	{
		return 1;
	}

	/**
	 * Public access to the task data.
	 * return array;
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * Serializes the task and it's data to json
	 */
	public function toJson()
	{
		return json_encode(array(
			get_class($this),
			$this->getVersion(),
			$this->_data,
			$this->_signature
		));
	}

	/**
	 * Creates a Task instance from a json snippet
	 * @return Sera_Task
	 */
	public static function fromJson($json)
	{
		$components = json_decode($json, true);
		list($class, $version, $data) = $components;

		$task = new $class($data);

		// add the optional signature component
		if(isset($components[3]))
		{
			$task->_signature = $components[3];
		}

		if($task->getVersion() != $version)
		{
			throw new Sera_Task_Exception(sprintf(
				'Task version %d does not match current version %d',
				$version,
				$task->getVersion()
			));
		}

		return $task;
	}
}

