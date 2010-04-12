<?php

/**
 * A worker that prompts the user what action to take
 */
class Sera_Queue_InteractiveQueueWorker extends Sera_Queue_QueueWorker
{
	private $_promptTasks=true;

	const DELAY_TIME = 30;

	/**
	 * Sets whether to prompt the user for all tasks
	 */
	public function setPromptTasks($value)
	{
		$this->_promptTasks = $value;
	}

	/* (non-phpdoc)
	 * @see Sera_Queue_QueueWorker::executeTask()
	 */
	protected function executeTask($task, $queue)
	{
		if(!$this->_promptTasks)
		{
			return parent::executeTask($task, $queue);
		}

		$logger = Ergo::loggerFor($this);
		$meta = $this->_getTaskMetadata($queue, $task);

		// print out the task
		printf("\n%s data => %s meta => %s\n", get_class($task),
			$this->_formatJsonForConsole(json_encode($task->getData())),
			$this->_formatJsonForConsole(json_encode($meta))
			);

		// execute the action chosen for the task
		switch(strtolower($this->_readCommand($this->_getTaskOperations($queue, $task))))
		{
			case '':
			case 'y':
				parent::executeTask($task, $queue);
				break;

			case 'n':
				$logger->info("releasing task %s for %d seconds", get_class($task), self::DELAY_TIME);
				$queue->release($task, self::DELAY_TIME);
				break;

			case 'd':
				$logger->info("deleting task %s", get_class($task));
				$queue->delete($task);
				break;

			case 'b':
				$logger->info("burying task %s", get_class($task));
				$queue->bury($task);
				break;

			case 'q':
				exit(Sera_WorkerFarm::SPAWN_TERMINATE);
				break;

			default:
				$logger->warn("not implemented command");
				break;
		}
	}

	/* (non-phpdoc)
	 * @see Sera_Worker::handle($e)
	 */
	public function handle($e)
	{
		$logger = Ergo::loggerFor($this);

		if($task = $this->getLastTask())
		{
			$prefix = "\033[31m";
			$suffix = "\033[0m";

			// print out the task
			printf("\n%sExecute Failed: %s => %s in %s%s\n",
				$prefix,
				get_class($task),
				$e->getMessage(),
				$e->getFile(),
				$suffix
				);

			$operations = array(
				'r'=>'r = release [default]',
				'd'=>'d = delete'
				);

			// execute the action chosen for the task
			switch(strtolower($this->_readCommand($operations)))
			{
				case '':
				case 'r':
					parent::handle($e);
					break;

				case 'd':
					$logger->info("deleting task %s", get_class($task));
					$this->getQueue()->delete($task);
					break;
			}
		}
	}

	/**
	 * Reads a command from the user
	 */
	private function _readCommand($operations)
	{
		while(true)
		{
			$prompt = 'Execute ('.implode(', ', $operations).')? ';
			$cmd = readline($prompt);

			if($cmd && !in_array(strtolower($cmd), array_keys($operations)))
			{
				printf("Unknown action\n\n");
			}
			else
			{
				printf("\n");
				return $cmd;
			}
		}
	}

	/**
	 * Gets an array of valid operations for a task
	 */
	private function _getTaskOperations($queue, $task)
	{
		// determine operations available
		$operations = array(
			'y'=>'y = yes [default]',
			'n'=>'n = no',
			'd'=>'d = delete',
			'q'=>'q = quit'
			);

		// add some queue specific actions
		if(method_exists($queue,'bury'))
		{
			$operations['b'] = 'b = bury';
		}

		return $operations;
	}

	/**
	 * Gets an array of key=>value metadata about a task
	 */
	private function _getTaskMetadata($queue, $task)
	{
		$meta = array(
			'priority'=>$task->getPriority()
			);

		if(method_exists($queue,'taskStats'))
		{
			$meta = array_merge($meta, $queue->taskStats($task));
		}

		return $meta;
	}

	/**
	 * Formats JSON for pretty printing in a console
	 */
	private function _formatJsonForConsole($json)
	{
		return str_replace(array('"',',','{','}'), array('',', ','{ ',' }'), $json);
	}
}
