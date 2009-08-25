<?php

/**
 * @author Lachlan Donald <lachlan@99designs.com>
 */
class QueueTest extends UnitTestCase
{
	public function testAddingToAQueue()
	{
		$queue = new Sera_Queue_ArrayQueue();
		$queue
			->listen('myqueue1')
			->listen('myqueue2')
			;

		$queue
			->select('myqueue1')
			->enqueue(Sera_Task_Null::create(array('mykey'=>'test1')))
			->select('myqueue2')
			->enqueue(Sera_Task_Null::create(array('mykey'=>'test2')))
			->select('myqueue3')
			->enqueue(Sera_Task_Null::create(array('mykey'=>'test2')))
			;

		$this->assertTaskData($queue->dequeue(), array('mykey'=>'test1'));
		$this->assertTaskData($queue->dequeue(), array('mykey'=>'test2'));
		$this->assertQueueEmpty($queue);
	}

	public function testIgnoringAQueue()
	{
		$queue = new Sera_Queue_ArrayQueue();
		$queue
			->listen('myqueue1')
			->listen('myqueue2')
			;

		$queue
			->select('myqueue1')
			->enqueue(Sera_Task_Null::create(array('mykey'=>'test1')))
			->select('myqueue2')
			->enqueue(Sera_Task_Null::create(array('mykey'=>'test2')))
			->enqueue(Sera_Task_Null::create(array('mykey'=>'test3')))
			;

		$this->assertTaskData($queue->dequeue(), array('mykey'=>'test1'));
		$this->assertTaskData($queue->dequeue(), array('mykey'=>'test2'));

		$queue->ignore('myqueue2');
		$this->assertQueueEmpty($queue);
	}

	public function assertTaskData($task, $data)
	{
		$this->assertTrue(is_object($task));
		$this->assertEqual($task->getData(), $data);
	}

	public function assertQueueEmpty($queue)
	{
		$this->assertNull($queue->dequeue());
	}
}
