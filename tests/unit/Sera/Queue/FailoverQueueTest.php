<?php

Mock::generate('Sera_Queue', 'MockQueue');

class Sera_Queue_FailoverQueueTest extends UnitTestCase
{
	public function testBasic()
	{
		$queue = new MockQueue();
		$queue->expectAtLeastOnce('select');
		$queue->expectOnce('enqueue');
		$queue->expectOnce('dequeue');

		$failoverQueue = new Sera_Queue_FailoverQueue(
			array(
				$queue,
			)
		);

		$task = Sera_Task_Null::create();

		$failoverQueue->select('test');
		$failoverQueue->enqueue($task);
		$task = $failoverQueue->dequeue();
	}

	public function testWithGoodQueues()
	{
		$queue1 = new MockQueue();
		$queue1->expectAtLeastOnce('select');
		$queue1->expectOnce('enqueue');
		$queue1->expectOnce('dequeue');

		$queue2 = new MockQueue();
		$queue2->expectNever('select');
		$queue2->expectNever('enqueue');
		$queue2->expectNever('dequeue');

		$failoverQueue = new Sera_Queue_FailoverQueue(
			array(
				$queue1,
				$queue2
			)
		);

		$task = Sera_Task_Null::create();

		$failoverQueue->select('test');
		$failoverQueue->enqueue($task);
		$task = $failoverQueue->dequeue();
	}

	public function testWithBadQueues()
	{
		$badQueue = new MockQueue();

		$exception = new Sera_Queue_QueueException();

		$badQueue->throwOn('enqueue', $exception);

		$badQueue->expectAtLeastOnce('select');
		$badQueue->expectOnce('enqueue');
		$badQueue->expectNever('dequeue');

		$goodQueue = new MockQueue();
		$goodQueue->expectAtLeastOnce('select');
		$goodQueue->expectOnce('enqueue');
		$goodQueue->expectOnce('dequeue');

		$failoverQueue = new Sera_Queue_FailoverQueue(
			array(
				$badQueue,
				$goodQueue
			)
		);

		$task = Sera_Task_Null::create();

		$failoverQueue->select('test');
		$failoverQueue->enqueue($task);
		$task = $failoverQueue->dequeue();
	}


}
