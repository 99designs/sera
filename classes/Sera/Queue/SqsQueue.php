<?php

/**
 * A queue driver for the Amazon SQS service
 */
class Sera_Queue_SqsQueue implements Sera_Queue
{
	private $_sqs;
	private $_accessKey;
	private $_secretKey;
	private $_pollRate;
	private $_messages=array();

	public function __construct($accessKey, $secretKey, $pollRate=1)
	{
		$this->_sqs = false;
		$this->_accessKey = $accessKey;
		$this->_secretKey = $secretKey;
		$this->_pollRate = $pollRate;
	}

	/* (non-phpdoc)
	 * @see Commerce_Queue::select
	 */
	public function select($queueName)
	{
		$this->_sqs = new Sera_Sqs_Client($this->_accessKey, $this->_secretKey, $queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Commerce_Queue::enqueue
	 */
	public function enqueue(Sera_Task $task)
	{
		$this->_sqs->SendMessage(urlencode($task->toJson()));
	}

	/* (non-phpdoc)
	 * @see Commerce_Queue::dequeue
	 */
	public function dequeue()
	{
		while(1)
		{
			// grab as many messages as we can get
			foreach($this->_sqs->ReceiveMessage() as $message)
			{
				array_push($this->_messages,$message);
			}

			// return just the first one
			if(count($this->_messages))
			{
				$message = array_shift($this->_messages);
				$task = Sera_Task_Builder::fromJson(urldecode($message->Body));
				$task->messageId = $message->MessageId;
				$task->receiptHandle = $message->ReceiptHandle;

				return $task;
			}

			//Ergo::loggerFor($this)->trace("No tasks yet, sleeping for $this->_pollRate second");
			sleep($this->_pollRate);
		}
	}

	/* (non-phpdoc)
	 * @see Commerce_Queue::delete
	 */
	public function delete(Sera_Task $task)
	{
		$this->_sqs->DeleteMessage($task->receiptHandle);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Commerce_Queue::release
	 */
	public function release(Sera_Task $task)
	{
		// do nothing, stuff gets released anyway.
		return $this;
	}
}
