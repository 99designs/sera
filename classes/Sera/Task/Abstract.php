<?php

/**
 * A task which can be executed (command pattern) and also have it's internal
 * data serialized into JSON
 */
abstract class Sera_Task_Abstract implements Sera_Task
{
	const EVENT_CREATE='Create';
	const EVENT_THAW='Thaw';

	protected $_data;
	protected $_signature=false;
	protected $_observers = array();

	/**
	 * Called by self::fromJson() or a static constructor in the concrete class.
	 */
	protected function __construct($data, $thaw = false)
	{
		$this->_data = $data;
		$this->notifyObservers($thaw ? self::EVENT_THAW : self::EVENT_CREATE);
	}

	/**
	 * Attach an observer that responds to notify
	 */
	public function attachObserver($observer)
	{
		$this->_observers []= $observer;
	}

	/**
	 * Notify all attached observers with an event
	 */
	public function notifyObservers($event)
	{
		foreach ($this->_observers as $observer)
			$observer->notify($event);

		$callback = array($this, 'on'.$event);
		if (is_callable($callback))
			call_user_func($callback);
	}

	// template methods invoked on events
	protected function onCreate() {}
	protected function onThaw() {}

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
		return 30; // 30 seconds
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

		if (!class_exists($class))
			throw new Sera_Task_TaskException("Task class '$class' does not exist");

		$task = new $class($data, true);

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
}

