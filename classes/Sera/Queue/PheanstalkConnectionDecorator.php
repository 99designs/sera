<?php

/**
 * Decorates which rethrows Pheanstalk exceptions as Sera ones.
 * @author Paul Annesley <paul@99designs.com>
 */
class Sera_Queue_PheanstalkConnectionDecorator
{
	private $_connection;

	/**
	 * @param Pheanstalk_Connection $connection
	 */
	public function __construct($connection)
	{
		$this->_connection = $connection;
	}

	/**
	 * @param string
	 * @param array
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		try
		{
			return call_user_func_array(array($this->_connection, $method), $parameters);
		}
		catch (Pheanstalk_Exception_ClientException $e)
		{
			throw new Sera_Queue_QueueException($e->getMessage(), $e->getCode());
		}
		catch (Pheanstalk_Exception_ServerException $e)
		{
			throw new Sera_Queue_QueueException($e->getMessage(), $e->getCode());
		}
	}
}
