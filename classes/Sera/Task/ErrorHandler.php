<?php

/**
 * A handler for a specific task, which releases a task before
 * displaying the error
 */
class Sera_Task_ErrorHandler extends Ergo_Error_ConsoleErrorHandler
{
	const RELEASE_DELAY=10; // 10 seconds

	private $_task, $_queue;

	public function __construct($queue, $task)
	{
		// don't show stack traces
		parent::__construct(false);

		$this->_queue = $queue;
		$this->_task = $task;
	}

	/* (non-phpdoc)
	 * @see Ergo_Error_ErrorHandler::handle()
	 */
	public function handle($e)
	{
		if ($this->isExceptionHalting($e))
		{
			$this->logger()->error(
				"worker terminated with a fatal error, releasing task for %d seconds",
					self::RELEASE_DELAY
					);

			$this->_queue->release($this->_task, self::RELEASE_DELAY);
		}

		parent::handle($e);
	}
}
