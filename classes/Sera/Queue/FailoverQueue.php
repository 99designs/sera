<?php

/**
 * A queue that queues tasks onto other delegate queues on failure
 * @author Paul Annesley <paul@99designs.com>
 */
class Sera_Queue_FailoverQueue implements Sera_Queue
{
	private $_delegates;
	private $_selected;
	private $_listening=array();
	private $_failoverCallback;

	/**
	 * @param Sera_Queue[] an array of queues to delegate failures to
	 */
	public function __construct($delegates)
	{
		$this->_delegates = $delegates;
	}

	/**
	 * @param callback - A callback to invoke whenever failover occurs
	 * The callback will be passed the exception that caused the failover
	 */
	public function onFailover($callback)
	{
		$this->_failoverCallback = $callback;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::select
	 */
	public function select($queueName)
	{
		$this->_selected = $queueName;
		$this->listen($queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::select
	 */
	public function listen($queueName)
	{
		$this->_listening[$queueName] = true;
		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::select
	 */
	public function ignore($queueName)
	{
		unset($this->_listening[$queueName]);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::enqueue
	 */
	public function enqueue($task)
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
	public function delete($task)
	{
		$args = func_get_args();
		return $this->_callDelegate(__FUNCTION__, $args);
	}

	/* (non-phpdoc)
	 * @see Sera_Queue::release
	 */
	public function release($task, $delay=false)
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
		while (!empty($this->_delegates))
		{
			$queue = $this->_delegates[0];

			try
			{
				// TODO: don't re-select if this delegate already selected
				if(isset($this->_selected))
				{
					$queue->select($this->_selected);
				}

				if(!empty($this->_listening))
				{
					foreach($this->_listening as $key=>$false) $queue->listen($key);
				}

				$result = call_user_func_array(
					array($queue, $method),
					$parameters
				);

				// maintain chainability
				return ($result === $queue) ? $this : $result;
			}
			catch (Sera_Queue_QueueException $e)
			{
				if (isset($this->_failoverCallback))
					call_user_func($this->_failoverCallback, $e);

				// don't try this delegate queue again
				array_shift($this->_delegates);
				continue;
			}
		}

		throw new Sera_Queue_QueueException();
	}
}
