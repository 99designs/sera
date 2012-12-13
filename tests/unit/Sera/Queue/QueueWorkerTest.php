<?php

class Sera_Task_QueueWorkerTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->queue = Mockery::mock(new Sera_Queue_ArrayQueue());
		$this->worker = new Sera_Queue_QueueWorker($this->queue);
	}

	public function testExecuteTask()
	{
		$task = Sera_Task_Null::create();

		$this
			->queue
			->shouldReceive('dequeue')
			->once()
			->andReturn($task);

		$this->assertEquals($this->worker->execute(), Sera_Worker::WORKER_SUCCESS);
	}
}
