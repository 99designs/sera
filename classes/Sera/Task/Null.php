<?php

class Sera_Task_Null extends Sera_Task_Abstract
{
	/**
	 * Public constructor.
	 * @param array data
	 */
	public static function create($data = array())
	{
		return new self($data);
	}

	/* (non-phpdoc)
	 * @see Task::execute()
	 */
	public function execute()
	{
	}

}
