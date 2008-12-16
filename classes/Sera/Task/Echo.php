<?php

class Sera_Task_Echo extends Sera_Task_Abstract
{
	public static function create($string)
	{
		return new self($string);
	}

	public function execute()
	{
		echo $this->_data;
	}
}
