<?php

class Sera_Task_Delay extends Sera_Task_Abstract
{
	public static function create($delay)
	{
		return new self($delay);
	}

	public function execute()
	{
		$delay = rand(1,$this->_data);
		echo "delaying $delay seconds\n";
		sleep($delay);
	}
}
