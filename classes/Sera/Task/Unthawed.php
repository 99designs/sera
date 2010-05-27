<?php

/**
 * Tasks that fail to unthaw are created as unthawed tasks
 */
class Sera_Task_Unthawed extends Sera_Task_Abstract
{
	public static function create($exception, $data)
	{
		return new self(array(
			'exception'=>serialize($exception),
			'data'=>$data
			));
	}

	public function execute()
	{
		$exception = unserialize($this->exception);
		throw $exception;
	}

	public function getData()
	{
		return $this->data;
	}
}
