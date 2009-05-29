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

