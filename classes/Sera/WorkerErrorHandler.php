<?php

/**
 * An error handler for a {@link Sera_Worker}
 */
class Sera_WorkerErrorHandler extends Ergo_Error_ConsoleErrorHandler
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

			try
			{
				$this->_queue->release($this->_task, self::RELEASE_DELAY);
			}
			catch(Exception $re)
			{
				$this->logger()->logException($re);
			}
		}

		parent::handle($e);
	}
}