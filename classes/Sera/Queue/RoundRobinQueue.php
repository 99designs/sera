<?php

/**
 * A queue that queues tasks onto other delegate queues in a round robin fashion.
 * @author Paul Annesley <paul@99designs.com>
 */
class Sera_Queue_RoundRobinQueue implements Sera_Queue
{
	private $_delegates;
	private $_index=0;
	private $_iterator;

	/**
	 * @param Sera_Queue[] an array of queues to delegate failures to
	 */
	public function __construct($delegates)
	{
		$this->_delegates = $delegates;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::select
	 */
	public function select($queueName)
	{
		foreach($this->_delegates as $delegate)
		{
			$delegate->select($queueName);
		}

		$this->listen($queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::select
	 */
	public function listen($queueName)
	{
		foreach($this->_delegates as $delegate)
		{
			$delegate->listen($queueName);
		}

		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::select
	 */
	public function ignore($queueName)
	{
		foreach($this->_delegates as $delegate)
		{
			$delegate->ignore($queueName);
		}

		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::enqueue
	 */
	public function enqueue(Sera_Task $task)
	{
		$args = func_get_args();
		return $this->_callDelegate(__FUNCTION__, $args);
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::dequeue
	 */
	public function dequeue($timeout=false)
	{
		$args = func_get_args();
		return $this->_callDelegate(__FUNCTION__, $args);
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::delete
	 */
	public function delete(Sera_Task $task)
	{
		$args = func_get_args();
		return $this->_callDelegate(__FUNCTION__, $args);
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::release
	 */
	public function release(Sera_Task $task, $delay=false)
	{
		$args = func_get_args();
		return $this->_callDelegate(__FUNCTION__, $args);
	}

	// ----------------------------------------

	/**
	 * Dispatch non-interface calls to delegate methods.
	 * @param string
	 * @param array
	 */
	public function __call($method,$params)
	{
		return $this->_callDelegate($method, $params);
	}

	// ----------------------------------------

	/**
	 * Dispatch all calls to delegate methods.
	 * @param string
	 * @param array
	 */
	private function _callDelegate($method, $parameters)
	{
		$queue = $this->_delegates[$this->_index];
		$this->_index += 1;
		$this->_index = $this->_index % count($this->_delegates);

		$result = call_user_func_array(
			array($queue, $method),
			$parameters
			);

		return $result;
	}
}
