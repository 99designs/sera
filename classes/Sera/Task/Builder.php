<?php

class Sera_Task_Builder
{
	/**
	 * Creates a Task instance from a json snippet
	 * @return Sera_Task
	 */
	public static function fromJson($json)
	{
		return Sera_Task_Abstract::fromJson($json);
	}
}

