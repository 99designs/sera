<?php

Mock::generate('Sera_Queue', 'MockQueue');

class MyTestTask extends Sera_Task_Abstract
{
	public function execute() { }
}

class Sera_Task_TaskTest extends UnitTestCase
{
	public function testBasic()
	{
		$task = Sera_Task_Abstract::fromJson('["MyTestTask",1,{"a":"b"},false]');
		$data = $task->getData();
		$this->assertEqual($data['a'], 'b');
	}

	public function testClassMissing()
	{
		$this->expectException('Sera_Task_TaskException');
		$task = Sera_Task_Abstract::fromJson('["ThisClassDoesNotExistTask",1,{"a":"b"},false]');
	}
}
