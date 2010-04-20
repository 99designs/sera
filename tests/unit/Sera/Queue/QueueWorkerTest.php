<?php

Mock::generate('Sera_Queue', 'MockQueue');
Mock::generate('Sera_Task', 'MockTask');

class Sera_Task_QueueWorkerTest extends UnitTestCase
{
	public function setUp()
	{
		$this->queue = new MockQueue();
		$this->worker = new Sera_Queue_QueueWorker($this->queue);
	}

	public function testExecuteTask()
	{
		$task = new MockTask();
		$this->queue->expectOnce('dequeue');
		$this->queue->setReturnValue('dequeue',$task);

		$this->assertEqual($this->worker->execute(), Sera_Worker::WORKER_SUCCESS);
	}
}
