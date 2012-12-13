<?php

class Sera_Queue_RoundRobinQueueTest extends PHPUnit_Framework_TestCase
{
	public function testMe()
	{
		$queue1 = Mockery::mock();
		$queue1
			->shouldReceive('select')->atLeast()->once()
			->shouldReceive('listen')->atLeast()->once();

		$queue2 = Mockery::mock();
		$queue2
			->shouldReceive('select')->atLeast()->once()
			->shouldReceive('listen')->atLeast()->once();

		$queue3 = Mockery::mock();
		$queue3
			->shouldReceive('select')->atLeast()->once()
			->shouldReceive('listen')->atLeast()->once();

		$queue = new Sera_Queue_RoundRobinQueue(array($queue1, $queue2, $queue3));

		$queue1->shouldReceive('enqueue')->once();
		$queue2->shouldReceive('enqueue')->once();
		$queue3->shouldReceive('enqueue')->once();

		$queue->select('test');
		$queue->enqueue(Mockery::mock());
		$queue->enqueue(Mockery::mock());
		$queue->enqueue(Mockery::mock());
	}
}
