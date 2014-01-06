<?php

/**
 * An error handler for a {@link Sera_Worker}
 */
class Sera_WorkerErrorHandler extends \Ergo\Error\ConsoleErrorHandler
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
	 * @see \Ergo\Error\ErrorHandler::handle()
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
				$this->logger()->error($re->getMessage(), array('exception' => $re));
			}
		}

		parent::handle($e);
	}
}
