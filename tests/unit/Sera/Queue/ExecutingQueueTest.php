<?php

class Sera_Queue_ExecutingQueueTest extends PHPUnit_Framework_TestCase
{
	public function testMe()
	{
		$queue = new Sera_Queue_ExecutingQueue();

		$task = Mockery::mock();
		$task->shouldReceive('execute')->once();

		$queue
			->select('test')
			->enqueue($task);
	}
}
