<?php

Mock::generate('Sera_Task', 'MockTask');

class Sera_Queue_ExecutingQueueTest extends UnitTestCase
{
	public function testMe()
	{
		$queue = new Sera_Queue_ExecutingQueue();

		$task = new MockTask();
		$task->expectOnce('execute');

		$queue
			->select('test')
			->enqueue($task);
	}
}
