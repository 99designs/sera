<?php

class MyTestTask extends Sera_Task_Abstract
{
	public function execute() { }
}

class Sera_Task_TaskTest extends PHPUnit_Framework_TestCase
{
	public function testBasic()
	{
		$task = Sera_Task_Abstract::fromJson('["MyTestTask",1,{"a":"b"},false]');
		$data = $task->getData();
		$this->assertEquals($data['a'], 'b');
	}

	public function testClassMissing()
	{
		$task = Sera_Task_Abstract::fromJson('["ThisClassDoesNotExistTask",1,{"a":"b"},false]');
		$this->assertInstanceOf('Sera_Task_Unthawed', $task);
	}
}
