<?php

/**
 * A worker that prompts the user what action to take
 */
class Sera_InteractiveWorker extends Sera_Worker
{
	/* (non-phpdoc)
	 * @see Sera_Process::onStart()
	 */
	protected function onStart()
	{
		$this->logger->info("running in interactive mode");
		$this->logger->info("starting workers");
	}

	/* (non-phpdoc)
	 * @see Sera_Process::main()
	 */
	public function onChildTerminate($exitcode)
	{
		$this->logger->trace("child process terminated with exit code %d", $exitcode);
	}

	/* (non-phpdoc)
	 * @see Sera_Worker::execute()
	 */
	public function execute($task, $queue)
	{
		$meta = $this->_getTaskMetadata($queue, $task);

		// print out the task
		printf("\n%s %s  %s\n", get_class($task),
			$this->_formatJsonForConsole(json_encode($task->getData())),
			$this->_formatJsonForConsole(json_encode($meta))
			);

		// execute the action chosen
		switch(strtolower($this->_readCommand($queue, $task)))
		{
			case 'x':
				parent::execute($task, $queue);
				break;

			case 'r':
				$this->logger->info("releasing task %s", get_class($task));
				$queue->release($task);
				break;

			case 'y':
				$this->logger->info("delaying task %s for 10 seconds", get_class($task));
				$queue->release($task, 10);
				break;

			case 'd':
				$this->logger->info("deleting task %s", get_class($task));
				$queue->delete($task);
				break;

			case 'b':
				$this->logger->info("burying task %s", get_class($task));
				$queue->bury($task);
				break;

			case 'q':
				exit(Sera_Process::SPAWN_TERMINATE);
				break;

			default:
				$this->logger->warn("not implemented command");
				break;
		}
	}

	/**
	 * Reads a command from the user for a task from the console
	 */
	private function _readCommand($queue, $task)
	{
		$operations = $this->_getTaskOperations($queue, $task);

		while(true)
		{
			$cmd = readline(sprintf("Action? %s : ", implode(' ', $operations)));

			if(!in_array(strtolower($cmd), array_keys($operations)))
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
			'x'=>'e[x]ecute',
			'r'=>'[r]elease',
			'y'=>'dela[y]',
			'd'=>'[d]elete',
			'q'=>'[q]uit'
			);

		// add some queue specific actions
		if(method_exists($queue,'bury'))
		{
			$operations['b'] = '[b]ury';
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
