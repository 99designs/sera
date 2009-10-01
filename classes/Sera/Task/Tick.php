<?php

class Sera_Task_Tick extends Sera_Task_Abstract
{
	public static function create($ticks)
	{
		return new self($ticks);
	}

	public function execute()
	{
		for($i=1; $i<=$this->_data; $i++)
		{
			sleep(1);
			echo "tick $i of {$this->_data}...\n";
		}
	}
}
