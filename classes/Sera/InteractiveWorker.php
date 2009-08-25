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
		$this->logger->info("child process terminated with exit code %d", $exitcode);
	}

	/* (non-phpdoc)
	 * @see Sera_Process::main()
	 */
	public function main()
	{
		$this->logger->info("listening for a task...");
		$queue = $this->queue();
		$task = $this->dequeue($queue);
		$operations = $this->_getTaskOperations($queue, $task);
		$meta = $this->_getTaskMetadata($queue, $task);

		printf("\n%s %s  %s\n", get_class($task),
			$this->_formatJsonForConsole(json_encode($task->getData())),
			$this->_formatJsonForConsole(json_encode($meta))
			);

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
				break;
			}
		}

		switch(strtolower($cmd))
		{
			case 'x':
				$this->execute($task, $queue);
				break;

			case 'r':
				$this->logger->info("releasing task");
				$queue->release($task);
				break;

			case 'y':
				$this->logger->info("delaying task for 10 seconds");
				$queue->release($task, 10);
				break;

			case 'd':
				$this->logger->info("deleting task");
				$queue->delete($task);
				break;

			case 'b':
				$this->logger->info("burying task");
				$queue->bury($task);
				break;

			case 'q':
				exit(Sera_Process::SPAWN_TERMINATE);
				break;

			default:
				$this->logger->warn("not implemented: %s", $cmd);
				break;
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
