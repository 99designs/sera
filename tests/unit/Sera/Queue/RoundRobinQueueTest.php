<?php

Mock::generate('Sera_Queue', 'MockQueue');
Mock::generate('Sera_Task', 'MockTask');

class Sera_Queue_RoundRobinQueueTest extends UnitTestCase
{
	public function testMe()
	{
		$queue1 = new MockQueue();
		$queue2 = new MockQueue();
		$queue3 = new MockQueue();
		$queue = new Sera_Queue_RoundRobinQueue(array($queue1, $queue2, $queue3));

		$queue1->expectOnce('enqueue');
		$queue2->expectOnce('enqueue');
		$queue3->expectOnce('enqueue');

		$queue->select('test');
		$queue->enqueue(new MockTask());
		$queue->enqueue(new MockTask());
		$queue->enqueue(new MockTask());
	}
}
