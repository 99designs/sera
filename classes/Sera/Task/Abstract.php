<?php

/**
 * A task which can be executed (command pattern) and also have it's internal
 * data serialized into JSON
 */
abstract class Sera_Task_Abstract implements Sera_Task
{
	const DEFAULT_TIMETORELEASE = 120; // 2 minutes

	protected $_data;
	protected $_signature=false;
	protected $_observers = array();

	/**
	 * Called by self::fromJson() or a static constructor in the concrete class.
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
	 * Returns the priority of the task
	 */
	public function getPriority()
	{
		return Sera_Task_Priority::NORMAL;
	}

	/**
	 * Returns the number of second before a task is released
	 * @return int
	 */
	public function getTimeToRelease()
	{
		return self::DEFAULT_TIMETORELEASE;
	}

	/**
	 * Public access to the task data.
	 * return array
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * Adds to the task data, overwriting items on key collision.
	 * @param array
	 */
	public function addData($data)
	{
		$this->_data = array_merge($this->_data, $data);
	}

	/**
	 * Serializes the task and it's data to json.
	 * @return string JSON
	 */
	public function toJson()
	{
		/*
		 * Note that _data is not accessed directly here; instead, we give
		 * subclasses an opportunity to add to the returned data in their
		 * getData overrides. -- craiga 12th Aug 2009
		 */
		return json_encode(array(
			get_class($this),
			$this->getVersion(),
			$this->getData(),
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
		list($class, $version, $data, $priority) = $components;

		try
		{
			if (!class_exists($class))
				throw new Sera_Task_TaskException("Task class '$class' does not exist");

			$task = new $class($data, true);
		}
		catch(Exception $e)
		{
			return Sera_Task_Unthawed::create($e, $data);
		}

		// add the optional signature component
		if(isset($components[3]))
		{
			$task->_signature = $components[3];
		}

		if($task->getVersion() != $version)
		{
			throw new Sera_Task_TaskException(sprintf(
				'Task version %d does not match current version %d',
				$version,
				$task->getVersion()
			));
		}

		return $task;
	}

	// ----------------------------------------------------
	// magic php methods

	public function __get($key)
	{
		return $this->_data[$key];
	}

	public function __isset($key)
	{
		return isset($this->_data[$key]);
	}
}
