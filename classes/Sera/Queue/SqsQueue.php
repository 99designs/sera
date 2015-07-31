<?php

/**
 * A queue driver for the Amazon SQS service
 */
class Sera_Queue_SqsQueue implements Sera_Queue
{
	private $_selected;
	private $_listening=array();
	private $_queues=array();

	private $_messages=array();
	private $_accessKey;
	private $_secretKey;
	private $_pollRate;
    private $_endpoint;

	/**
	 * Constructor
	 */
	public function __construct($accessKey, $secretKey, $pollRate=1, $endpoint=null)
	{
		$this->_sqs = false;
		$this->_accessKey = $accessKey;
		$this->_secretKey = $secretKey;
		$this->_pollRate = $pollRate;
        $this->_endpoint = $endpoint;
	}

	/* (non-phpdoc)
	 * @see Commerce_Queue::select
	 */
	public function select($queueName)
	{
		$this->_selected = new Sera_Sqs_Client($this->_accessKey, $this->_secretKey, $queueName, $this->_endpoint);
		$this->listen($queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Commerce_Queue::listen
	 */
	public function listen($queueName)
	{
		$this->_listening[$queueName] = new Sera_Sqs_Client($this->_accessKey, $this->_secretKey, $queueName);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Commerce_Queue::ignore
	 */
	public function ignore($queueName)
	{
		unset($this->_listening[$queueName]);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Commerce_Queue::enqueue
	 */
	public function enqueue(Sera_Task $task)
	{
		$this->_selected->SendMessage(urlencode($task->toJson()));
	}

	/* (non-phpdoc)
	 * @see Commerce_Queue::dequeue
	 */
	public function dequeue($timeout=false)
	{
		if($timeout !== false)
		{
			throw new InvalidArgumentException("Timeout not implemented");
		}

		while(true)
		{
			$queues = $this->_listening;

			foreach($queues as $queue)
			{
				foreach($queue->ReceiveMessage() as $message)
				{
					$this->_messages[$queue->queueName][] = $message;
				}

				// return just the first available message
				foreach($this->_messages as $queueName=>$messages)
				{
					foreach($messages as $message)
					{
						$task = Sera_Task_Builder::fromJson(urldecode($message->Body));
						$task->messageId = $message->MessageId;
						$task->receiptHandle = $message->ReceiptHandle;
						$task->queueName = $queueName;

						// change the SQS release delay
						$queue->ChangeMessageVisibility($message->ReceiptHandle, $task->getTimeToRelease());

						return $task;
					}
				}
			}

			// shuffling queues ensures no queue receives better treatment
			shuffle($queues);
			sleep($this->_pollRate);
		}
	}

	/* (non-phpdoc)
	 * @see Commerce_Queue::delete
	 */
	public function delete(Sera_Task $task)
	{
		$this->_listening[$task->queueName]->DeleteMessage($task->receiptHandle);
		return $this;
	}

	/* (non-phpdoc)
	 * @see Commerce_Queue::release
	 */
	public function release(Sera_Task $task, $delay=false)
	{
		// change the SQS release delay, this isn't exactly a delay..
		$this->_listening[$task->queueName]->ChangeMessageVisibility(
			$task->receiptHandle, (int) $delay);

		return $this;
	}

	/**
	 * Return some stats about the queue
	 */
	public function stats()
	{
		$stats = array('ready'=>0);

		foreach($this->_listening as $queue)
		{
			$stats['ready'] += $queue->GetQueueAttributes('ApproximateNumberOfMessages');
		}

		return $stats;
	}
}
