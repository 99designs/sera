<?php

/**
 * @author Lachlan Donald <lachlan@99designs.com>
 */
class QueueTest extends UnitTestCase
{
	public function testAddingToAQueue()
	{
		$queue = new Sera_Queue_ArrayQueue();
		$queue->select('myqueue');

		// add a queuable to the queue
		$queue->enqueue(Sera_Task_Null::create(array('mykey'=>'test1')));
		$queue->enqueue(Sera_Task_Null::create(array('mykey'=>'test2')));

		// get the first task from the queue
		$task1 = $queue->dequeue();

		// check the job was extracted from the queue
		$this->assertTrue(is_object($task1));
		$this->assertEqual($task1->getData(), array('mykey'=>'test1'));

		// get the first task from the queue
		$task2 = $queue->dequeue();

		// check the job was extracted from the queue
		$this->assertTrue(is_object($task2));
		$this->assertEqual($task2->getData(), array('mykey'=>'test2'));

		$this->assertNull($queue->dequeue());
	}
}
