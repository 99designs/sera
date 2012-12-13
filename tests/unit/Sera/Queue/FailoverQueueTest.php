<?php

class Sera_Queue_FailoverQueueTest extends PHPUnit_Framework_TestCase
{
	public function testBasic()
	{
		$queue = Mockery::mock();
		$queue->shouldReceive('select')->atLeast()->once();
		$queue->shouldReceive('listen')->atLeast()->once();
		$queue->shouldReceive('enqueue')->once();
		$queue->shouldReceive('dequeue')->once();

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
		$queue1 = Mockery::mock();
		$queue1->shouldReceive('select')->atLeast()->once();
		$queue1->shouldReceive('listen')->atLeast()->once();
		$queue1->shouldReceive('enqueue')->once();
		$queue1->shouldReceive('dequeue')->once();

		$queue2 = Mockery::mock();
		$queue2->shouldReceive('select')->never();
		$queue2->shouldReceive('listen')->never();
		$queue2->shouldReceive('enqueue')->never();
		$queue2->shouldReceive('dequeue')->never();

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
		$badQueue = Mockery::mock();

		$exception = new Sera_Queue_QueueException();

		$badQueue->shouldReceive('enqueue')->once()->andThrow($exception);
		$badQueue->shouldReceive('listen')->atLeast()->once();
		$badQueue->shouldReceive('select')->atLeast()->once();
		$badQueue->shouldReceive('dequeue')->never();

		$goodQueue = Mockery::mock();
		$goodQueue->shouldReceive('listen')->atLeast()->once();
		$goodQueue->shouldReceive('select')->atLeast()->once();
		$goodQueue->shouldReceive('enqueue')->once();
		$goodQueue->shouldReceive('dequeue')->once();

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

	public function testOnFailoverReceivesExceptionFromFailingQueue()
	{
		$badQueue = Mockery::mock();

		$exception = new Sera_Queue_QueueException();

		$badQueue->shouldReceive('enqueue')->once()->andThrow( $exception);
		$badQueue->shouldReceive('select')->atLeast()->once();
		$badQueue->shouldReceive('listen')->atLeast()->once();
		$badQueue->shouldReceive('dequeue')->never();

		$goodQueue = Mockery::mock();
		$goodQueue->shouldReceive('select')->atLeast()->once();
		$goodQueue->shouldReceive('listen')->atLeast()->once();
		$goodQueue->shouldReceive('enqueue')->once();


		$failoverQueue = new Sera_Queue_FailoverQueue(
			array(
				$badQueue,
				$goodQueue
			)
		);
		$test = $this;
		$failoverQueue->onFailover(function($e) use ($test) {
			$test->exception = $e;
		});

		$task = Sera_Task_Null::create();

		$failoverQueue->select('test');
		$failoverQueue->enqueue($task);

		$this->assertSame($this->exception, $exception);
	}
}
